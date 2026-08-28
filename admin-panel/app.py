#!/usr/bin/env python3
"""
Admin Panel — Results Infra
Painel unificado: DNS (PowerDNS API), VHosts (MySQL), LDAP (OpenLDAP).
Autenticação LDAP com flags de acesso por grupo (memberOf).
"""
import os
import json
from functools import wraps
from flask import Flask, render_template, request, redirect, url_for, session, jsonify
import ldap3
import requests
import pymysql

app = Flask(__name__)
app.secret_key = os.getenv("FLASK_SECRET_KEY", os.urandom(32).hex())

# ── Config ──────────────────────────────────────────────────────────────────
CFG = {
    "mysql": {
        "host": os.getenv("MYSQL_HOST", "10.10.2.99"),
        "port": int(os.getenv("MYSQL_PORT", "3306")),
        "db": os.getenv("MYSQL_DATABASE", "results"),
        "user": os.getenv("MYSQL_USER", "resultsdba"),
        "pass": os.getenv("MYSQL_PASSWORD", ""),
    },
    "vhosts_table": os.getenv("VHOSTS_TABLE", "apache_vhosts"),
    "pdns_api": os.getenv("PDNS_API_URL", "http://pdns-auth:8081"),
    "pdns_key": os.getenv("PDNS_API_KEY", ""),
    "ldap": {
        "uri": os.getenv("LDAP_URI", "ldap://results-ldap:389"),
        "base": os.getenv("LDAP_BASE_DN", "dc=results,dc=com,dc=br"),
        "bind_dn": os.getenv("LDAP_BIND_DN", "cn=admin,dc=results,dc=com,dc=br"),
        "bind_pw": os.getenv("LDAP_BIND_PASSWORD", ""),
        "users_dn": os.getenv("LDAP_USERS_DN", "ou=people,dc=results,dc=com,dc=br"),
        "groups_dn": os.getenv("LDAP_GROUPS_DN", "ou=groups,dc=results,dc=com,dc=br"),
    },
    "groups": {
        "dns": os.getenv("ADMIN_GROUP_DNS", "cn=dns-admins,ou=groups,dc=results,dc=com,dc=br"),
        "vhosts": os.getenv("ADMIN_GROUP_VHOSTS", "cn=vhost-admins,ou=groups,dc=results,dc=com,dc=br"),
        "ldap": os.getenv("ADMIN_GROUP_LDAP", "cn=ldap-admins,ou=groups,dc=results,dc=com,dc=br"),
        "mail": os.getenv("ADMIN_GROUP_MAIL", "cn=ldap-admins,ou=groups,dc=results,dc=com,dc=br"),
    },
}

# ── LDAP helpers ─────────────────────────────────────────────────────────────
def ldap_connect():
    server = ldap3.Server(CFG["ldap"]["uri"], get_info=ldap3.ALL)
    conn = ldap3.Connection(server, CFG["ldap"]["bind_dn"], CFG["ldap"]["bind_pw"], auto_bind=True)
    return conn

def ldap_auth(user: str, password: str) -> dict | None:
    """Autentica usuário e retorna {dn, cn, groups} ou None."""
    try:
        conn = ldap_connect()
        user_dn = None
        cn = user
        groups = []

        # busca o usuário em ou=people
        conn.search(CFG["ldap"]["users_dn"], f"(uid={ldap3.utils.conv.escape_filter_chars(user)})",
                     attributes=["cn", "memberOf"])
        if conn.entries:
            entry = conn.entries[0]
            user_dn = entry.entry_dn
            cn = str(entry.cn) if entry.cn else user
            groups = [str(g) for g in entry.memberOf.values] if entry.memberOf else []
        else:
            # fallback: tenta cn=admin direto no base DN
            conn.search(CFG["ldap"]["base"], f"(cn={ldap3.utils.conv.escape_filter_chars(user)})",
                         attributes=["cn", "memberOf"])
            if conn.entries:
                entry = conn.entries[0]
                user_dn = entry.entry_dn
                cn = str(entry.cn) if entry.cn else user
                groups = [str(g) for g in entry.memberOf.values] if entry.memberOf else []

        if not user_dn:
            return None

        conn.unbind()
        # tenta bind com a senha do usuário
        server2 = ldap3.Server(CFG["ldap"]["uri"], get_info=ldap3.ALL)
        conn2 = ldap3.Connection(server2, user_dn, password, auto_bind=True)
        conn2.unbind()
        return {"dn": user_dn, "cn": cn, "groups": groups}
    except Exception:
        return None

# ── Auth decorator ───────────────────────────────────────────────────────────
def login_required(f):
    @wraps(f)
    def wrapper(*args, **kwargs):
        if "user" not in session:
            return redirect(url_for("login"))
        return f(*args, **kwargs)
    return wrapper

def user_flags():
    """Retorna dict com flags de acesso baseado nos grupos do usuário."""
    groups = session.get("groups", [])
    return {
        "dns": CFG["groups"]["dns"] in groups,
        "vhosts": CFG["groups"]["vhosts"] in groups,
        "ldap": CFG["groups"]["ldap"] in groups,
        "mail": CFG["groups"]["mail"] in groups,
    }

def get_user_groups(user_dn: str) -> list:
    """Busca grupos a que o usuário pertence (via member de cada grupo)."""
    try:
        conn = ldap_connect()
        groups = []
        for group_dn in CFG["groups"].values():
            conn.search(group_dn, f"(member={ldap3.utils.conv.escape_filter_chars(user_dn)})", attributes=["cn"])
            if conn.entries:
                groups.append(group_dn)
        conn.unbind()
        return groups
    except Exception:
        return []

# ── MySQL helpers ────────────────────────────────────────────────────────────
def mysql_conn():
    return pymysql.connect(
        host=CFG["mysql"]["host"], port=CFG["mysql"]["port"],
        database=CFG["mysql"]["db"], user=CFG["mysql"]["user"],
        password=CFG["mysql"]["pass"],
        charset="utf8mb4", cursorclass=pymysql.cursors.DictCursor,
    )

# ── Routes ───────────────────────────────────────────────────────────────────

@app.route("/login", methods=["GET", "POST"])
def login():
    error = None
    if request.method == "POST":
        user = request.form.get("user", "").strip()
        pw = request.form.get("pass", "")
        info = ldap_auth(user, pw)
        if info:
            session["user"] = info["cn"]
            session["dn"] = info["dn"]
            session["groups"] = get_user_groups(info["dn"])
            return redirect(url_for("dashboard"))
        error = "Usuário ou senha inválidos"
    return render_template("login.html", error=error)

@app.route("/logout")
def logout():
    session.clear()
    return redirect(url_for("login"))

# ── Dashboard ────────────────────────────────────────────────────────────────
@app.route("/")
@login_required
def dashboard():
    flags = user_flags()
    cards = []
    if flags["dns"]:
        cards.append({"url": "/dns", "icon": "🌐", "title": "DNS — PowerDNS",
                       "desc": "Gerenciar zonas, registros A, CNAME, MX, TXT."})
    if flags["vhosts"]:
        cards.append({"url": "/vhosts", "icon": "🏠", "title": "Virtual Hosts — Apache",
                       "desc": "CRUD de virtual hosts. Ativar/desativar, backends, SSL."})
    if flags["ldap"]:
        cards.append({"url": "/ldap", "icon": "👥", "title": "LDAP — Usuários/Grupos",
                       "desc": "Gerenciar usuários, grupos e permissões."})
    if flags["mail"]:
        cards.append({"url": "/mail-groups", "icon": "✉️", "title": "Email — Grupos (Aliases)",
                       "desc": "Listas de distribuição: financeiro@, compras@, vendas@..."})
    return render_template("dashboard.html", user=session["user"], cards=cards)

# ── DNS ──────────────────────────────────────────────────────────────────────
PDNS = f"{CFG['pdns_api']}/api/v1/servers/localhost"

def pdns(method, path, data=None):
    h = {"X-API-Key": CFG["pdns_key"]}
    url = f"{PDNS}{path}"
    if method == "GET":
        return requests.get(url, headers=h, timeout=10)
    return requests.request(method, url, headers=h, json=data, timeout=10)

@app.route("/dns")
@login_required
def dns_list():
    if not user_flags()["dns"]:
        return redirect(url_for("dashboard"))
    try:
        r = pdns("GET", "/zones")
        zones = r.json() if r.ok else []
    except Exception:
        zones = []
    return render_template("dns_list.html", user=session["user"], zones=zones)

@app.route("/dns/<zone_id>")
@login_required
def dns_zone(zone_id):
    if not user_flags()["dns"]:
        return redirect(url_for("dashboard"))
    try:
        r = pdns("GET", f"/zones/{zone_id}")
        zone = r.json()
        rrsets = sorted(zone.get("rrsets", []), key=lambda x: (x["name"], x["type"]))
    except Exception:
        return render_template("dns_zone.html", user=session["user"], error="Erro ao carregar zona")
    return render_template("dns_zone.html", user=session["user"], zone=zone, rrsets=rrsets)

@app.route("/dns/<zone_id>/add", methods=["POST"])
@login_required
def dns_add_record(zone_id):
    if not user_flags()["dns"]:
        return redirect(url_for("dashboard"))
    name = request.form.get("name", "").strip()
    rtype = request.form.get("type", "A").strip()
    content = request.form.get("content", "").strip()
    ttl = int(request.form.get("ttl", 300))
    if not name or not content:
        return redirect(url_for("dns_zone", zone_id=zone_id))
    fqdn = f"{name}.{zone_id}" if name != "@" else zone_id
    data = {
        "name": fqdn,
        "type": rtype,
        "ttl": ttl,
        "changetype": "REPLACE",
        "records": [{"content": content, "disabled": False}],
    }
    try:
        pdns("PATCH", f"/zones/{zone_id}", {"rrsets": [data]})
    except Exception:
        pass
    return redirect(url_for("dns_zone", zone_id=zone_id))

@app.route("/dns/<zone_id>/delete", methods=["POST"])
@login_required
def dns_delete_record(zone_id):
    if not user_flags()["dns"]:
        return redirect(url_for("dashboard"))
    name = request.form.get("name", "")
    rtype = request.form.get("type", "")
    if not name or not rtype:
        return redirect(url_for("dns_zone", zone_id=zone_id))
    data = {"name": name, "type": rtype, "changetype": "DELETE", "records": []}
    try:
        pdns("PATCH", f"/zones/{zone_id}", {"rrsets": [data]})
    except Exception:
        pass
    return redirect(url_for("dns_zone", zone_id=zone_id))

@app.route("/dns/create", methods=["POST"])
@login_required
def dns_create_zone():
    if not user_flags()["dns"]:
        return redirect(url_for("dashboard"))
    name = request.form.get("name", "").strip()
    if not name or "." not in name:
        return redirect(url_for("dns_list"))
    if not name.endswith("."):
        name += "."
    data = {"name": name, "kind": "Native",
            "nameservers": ["ns1.results.com.br.", "ns2.results.com.br."]}
    try:
        pdns("POST", "/zones", data)
    except Exception:
        pass
    return redirect(url_for("dns_list"))

# ── VHosts ───────────────────────────────────────────────────────────────────
@app.route("/vhosts")
@login_required
def vhosts_list():
    if not user_flags()["vhosts"]:
        return redirect(url_for("dashboard"))
    try:
        with mysql_conn() as db:
            with db.cursor() as cur:
                cur.execute(f"SELECT * FROM `{CFG['vhosts_table']}` ORDER BY server_name")
                rows = cur.fetchall()
    except Exception as e:
        rows = []
        error = str(e)
    else:
        error = None
    return render_template("vhosts_list.html", user=session["user"], rows=rows, error=error)

@app.route("/vhosts/save", methods=["POST"])
@login_required
def vhosts_save():
    if not user_flags()["vhosts"]:
        return redirect(url_for("dashboard"))
    vid = request.form.get("id")
    data = {
        "server_name": request.form.get("server_name", "").strip(),
        "server_alias": request.form.get("server_alias", "").strip(),
        "backend_scheme": request.form.get("backend_scheme", "http"),
        "backend_host": request.form.get("backend_host", "").strip(),
        "backend_port": int(request.form.get("backend_port", 80)),
        "backend_path": "/" + request.form.get("backend_path", "/").strip("/"),
        "ssl_insecure": 1 if request.form.get("ssl_insecure") else 0,
        "enabled": 1 if request.form.get("enabled") else 0,
    }
    tbl = CFG["vhosts_table"]
    try:
        with mysql_conn() as db:
            with db.cursor() as cur:
                if vid:
                    cur.execute(f"UPDATE `{tbl}` SET server_name=%(server_name)s, server_alias=%(server_alias)s, backend_scheme=%(backend_scheme)s, backend_host=%(backend_host)s, backend_port=%(backend_port)s, backend_path=%(backend_path)s, ssl_insecure=%(ssl_insecure)s, enabled=%(enabled)s, updated_at=NOW() WHERE id=%(id)s", {**data, "id": int(vid)})
                else:
                    cur.execute(f"INSERT INTO `{tbl}` (server_name, server_alias, backend_scheme, backend_host, backend_port, backend_path, ssl_insecure, enabled) VALUES (%(server_name)s, %(server_alias)s, %(backend_scheme)s, %(backend_host)s, %(backend_port)s, %(backend_path)s, %(ssl_insecure)s, %(enabled)s)", data)
            db.commit()
    except Exception:
        pass
    return redirect(url_for("vhosts_list"))

@app.route("/vhosts/toggle", methods=["POST"])
@login_required
def vhosts_toggle():
    if not user_flags()["vhosts"]:
        return jsonify(ok=False)
    vid = request.json.get("id")
    enabled = request.json.get("enabled", 0)
    tbl = CFG["vhosts_table"]
    try:
        with mysql_conn() as db:
            with db.cursor() as cur:
                cur.execute(f"UPDATE `{tbl}` SET enabled=%s, updated_at=NOW() WHERE id=%s", (enabled, vid))
            db.commit()
    except Exception:
        return jsonify(ok=False)
    return jsonify(ok=True)

@app.route("/vhosts/delete", methods=["POST"])
@login_required
def vhosts_delete():
    if not user_flags()["vhosts"]:
        return jsonify(ok=False)
    vid = request.json.get("id")
    tbl = CFG["vhosts_table"]
    try:
        with mysql_conn() as db:
            with db.cursor() as cur:
                cur.execute(f"DELETE FROM `{tbl}` WHERE id=%s", (vid,))
            db.commit()
    except Exception:
        return jsonify(ok=False)
    return jsonify(ok=True)

# ── LDAP ─────────────────────────────────────────────────────────────────────
@app.route("/ldap")
@login_required
def ldap_users():
    if not user_flags()["ldap"]:
        return redirect(url_for("dashboard"))
    error = None
    users = []
    groups = []
    try:
        conn = ldap_connect()
        conn.search(CFG["ldap"]["users_dn"], "(objectClass=inetOrgPerson)",
                     attributes=["uid", "cn", "mail", "memberOf"])
        for e in conn.entries:
            users.append({
                "dn": e.entry_dn,
                "uid": str(e.uid) if e.uid else "",
                "cn": str(e.cn) if e.cn else "",
                "mail": str(e.mail) if e.mail else "",
            })
        conn.search(CFG["ldap"]["groups_dn"], "(objectClass=groupOfNames)",
                     attributes=["cn"])
        groups = [str(e.cn) for e in conn.entries]
        conn.unbind()
    except Exception as e:
        error = str(e)
    return render_template("ldap_list.html", user=session["user"], users=users, groups=groups, error=error, groups_dn=CFG["ldap"]["groups_dn"])

@app.route("/ldap/user/save", methods=["POST"])
@login_required
def ldap_user_save():
    if not user_flags()["ldap"]:
        return redirect(url_for("dashboard"))
    uid = request.form.get("uid", "").strip()
    cn = request.form.get("cn", "").strip()
    mail = request.form.get("mail", "").strip()
    password = request.form.get("password", "").strip()
    old_uid = request.form.get("old_uid", "").strip()
    objclass = request.form.get("objectClass", "inetOrgPerson").strip()
    if not uid or not cn:
        return redirect(url_for("ldap_users"))
    dn = f"uid={uid},{CFG['ldap']['users_dn']}"
    # Hierarquia real do diretório: person < organizationalPerson < inetOrgPerson
    classes = ["top", "person", "organizationalPerson"]
    if objclass == "inetOrgPerson":
        classes.append("inetOrgPerson")
    if objclass == "posixAccount":
        classes.extend(["inetOrgPerson", "posixAccount"])
    try:
        conn = ldap_connect()
        if old_uid and old_uid != uid:
            old_dn = f"uid={old_uid},{CFG['ldap']['users_dn']}"
            conn.modify_dn(old_dn, f"uid={uid}")
        attrs = {"objectClass": classes, "cn": cn, "sn": cn, "uid": uid}
        if mail:
            attrs["mail"] = mail
        if password:
            attrs["userPassword"] = password
        if objclass == "posixAccount":
            # posixAccount exige atributos numéricos
            attrs["uidNumber"] = request.form.get("uidNumber", "10000")
            attrs["gidNumber"] = request.form.get("gidNumber", "10000")
            attrs["homeDirectory"] = request.form.get("homeDirectory", f"/home/{uid}")
        if old_uid:
            mods = {"cn": [(ldap3.MODIFY_REPLACE, [cn])]}
            if mail:
                mods["mail"] = [(ldap3.MODIFY_REPLACE, [mail])]
            if password:
                mods["userPassword"] = [(ldap3.MODIFY_REPLACE, [password])]
            conn.modify(dn, mods)
        else:
            conn.add(dn, attributes=attrs)
        conn.unbind()
    except Exception:
        pass
    return redirect(url_for("ldap_users"))

@app.route("/ldap/group/save", methods=["POST"])
@login_required
def ldap_group_save():
    if not user_flags()["ldap"]:
        return redirect(url_for("dashboard"))
    cn = request.form.get("cn", "").strip()
    if not cn:
        return redirect(url_for("ldap_users"))
    dn = f"cn={cn},{CFG['ldap']['groups_dn']}"
    try:
        conn = ldap_connect()
        conn.add(dn, attributes={"objectClass": ["top", "groupOfNames"], "cn": cn,
                                  "member": CFG["ldap"]["bind_dn"]})
        conn.unbind()
    except Exception:
        pass
    return redirect(url_for("ldap_users"))

@app.route("/ldap/group/delete", methods=["POST"])
@login_required
def ldap_group_delete():
    if not user_flags()["ldap"]:
        return redirect(url_for("dashboard"))
    dn = request.form.get("dn", "")
    if dn:
        try:
            conn = ldap_connect()
            conn.delete(dn)
            conn.unbind()
        except Exception:
            pass
    return redirect(url_for("ldap_users"))

@app.route("/ldap/user/delete", methods=["POST"])
@login_required
def ldap_user_delete():
    if not user_flags()["ldap"]:
        return redirect(url_for("dashboard"))
    dn = request.form.get("dn", "")
    if dn:
        try:
            conn = ldap_connect()
            conn.delete(dn)
            conn.unbind()
        except Exception:
            pass
    return redirect(url_for("ldap_users"))

# ── Email Groups (aliases de distribuição) ───────────────────────────────────
MAIL_ALIAS_TABLE = os.getenv("MAIL_ALIAS_TABLE", "alias")
MAIL_MAILBOX_TABLE = os.getenv("MAIL_MAILBOX_TABLE", "mailbox")

@app.route("/mail-groups")
@login_required
def mail_groups_list():
    if not user_flags()["mail"]:
        return redirect(url_for("dashboard"))
    error = None
    aliases = []
    mailboxes = []
    try:
        with mysql_conn() as db:
            with db.cursor() as cur:
                cur.execute(f"SELECT id, address, goto, domain, active FROM `{MAIL_ALIAS_TABLE}` ORDER BY address")
                aliases = cur.fetchall()
                cur.execute(f"SELECT username FROM `{MAIL_MAILBOX_TABLE}` WHERE active=1 ORDER BY username")
                mailboxes = [r["username"] for r in cur.fetchall()]
    except Exception as e:
        error = str(e)
    return render_template("mail_groups.html", user=session["user"], aliases=aliases, mailboxes=mailboxes, error=error)

@app.route("/mail-groups/save", methods=["POST"])
@login_required
def mail_groups_save():
    if not user_flags()["mail"]:
        return redirect(url_for("dashboard"))
    aid = request.form.get("id", "").strip()
    address = request.form.get("address", "").strip().lower()
    members = request.form.get("members", "").strip()
    active = 1 if request.form.get("active") else 0
    if not address or not members:
        return redirect(url_for("mail_groups_list"))
    # normaliza a lista de membros (vírgula ou quebra de linha)
    parts = [p.strip() for p in members.replace("\n", ",").split(",") if p.strip()]
    goto = ", ".join(parts)
    try:
        with mysql_conn() as db:
            with db.cursor() as cur:
                if aid:
                    cur.execute(f"UPDATE `{MAIL_ALIAS_TABLE}` SET address=%s, goto=%s, active=%s, change_date=NOW() WHERE id=%s",
                                (address, goto, active, int(aid)))
                else:
                    cur.execute(f"INSERT INTO `{MAIL_ALIAS_TABLE}` (address, goto, domain, create_date, change_date, active) VALUES (%s, %s, '', NOW(), NOW(), %s)",
                                (address, goto, active))
            db.commit()
    except Exception:
        pass
    return redirect(url_for("mail_groups_list"))

@app.route("/mail-groups/delete", methods=["POST"])
@login_required
def mail_groups_delete():
    if not user_flags()["mail"]:
        return redirect(url_for("dashboard"))
    aid = request.form.get("id", "").strip()
    if aid:
        try:
            with mysql_conn() as db:
                with db.cursor() as cur:
                    cur.execute(f"DELETE FROM `{MAIL_ALIAS_TABLE}` WHERE id=%s", (int(aid),))
                db.commit()
        except Exception:
            pass
    return redirect(url_for("mail_groups_list"))

@app.route("/mail-groups/member/remove", methods=["POST"])
@login_required
def mail_groups_member_remove():
    if not user_flags()["mail"]:
        return redirect(url_for("dashboard"))
    aid = request.form.get("id", "").strip()
    member = request.form.get("member", "").strip()
    if not aid or not member:
        return redirect(url_for("mail_groups_list"))
    try:
        with mysql_conn() as db:
            with db.cursor() as cur:
                cur.execute(f"SELECT goto FROM `{MAIL_ALIAS_TABLE}` WHERE id=%s", (int(aid),))
                row = cur.fetchone()
                if not row:
                    return redirect(url_for("mail_groups_list"))
                goto = row["goto"] or ""
                parts = [p.strip() for p in goto.split(",") if p.strip()]
                parts = [p for p in parts if p.lower() != member.lower()]
                cur.execute(f"UPDATE `{MAIL_ALIAS_TABLE}` SET goto=%s, change_date=NOW() WHERE id=%s",
                            (", ".join(parts), int(aid)))
            db.commit()
    except Exception:
        pass
    return redirect(url_for("mail_groups_list"))

# ── Main ─────────────────────────────────────────────────────────────────────
if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=False)
