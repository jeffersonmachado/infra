'use strict';

function parseInitialUsers(env = process.env) {
  const users = [];
  const seen = new Set();

  const pushUser = (user) => {
    const name = String(user.name || user.username || '').trim();
    if (!name || seen.has(name)) return;
    seen.add(name);
    users.push({
      name,
      password: String(user.password || ''),
      active: user.active === false ? 0 : 1,
      admin: user.admin !== false,
    });
  };

  if (env.ICINGAWEB_ADMIN_USER && env.ICINGAWEB_ADMIN_PASS) {
    pushUser({
      name: env.ICINGAWEB_ADMIN_USER,
      password: env.ICINGAWEB_ADMIN_PASS,
      active: true,
      admin: true,
    });
  }

  if (env.OBSERVE_INITIAL_USERS_JSON) {
    const parsed = JSON.parse(env.OBSERVE_INITIAL_USERS_JSON);
    if (!Array.isArray(parsed)) {
      throw new Error('OBSERVE_INITIAL_USERS_JSON deve ser um array');
    }
    for (const user of parsed) pushUser(user || {});
  }

  return users;
}

async function bootstrapInitialUsers(pool, env = process.env, logger = console) {
  const users = parseInitialUsers(env).filter((user) => user.password);
  const forcePasswordUpdate = String(env.OBSERVE_BOOTSTRAP_FORCE_PASSWORD_UPDATE || '').toLowerCase() === 'true';
  if (users.length === 0) {
    logger.log?.(JSON.stringify({
      level: 'info',
      service: 'r-observe-api',
      msg: 'No initial users configured',
      ts: new Date().toISOString(),
    }));
    return { users: 0 };
  }

  await pool.query(`
    INSERT INTO icingaweb_group ("name", "ctime", "mtime")
    VALUES ('Administrators', NOW(), NOW())
    ON CONFLICT ("name") DO NOTHING
  `);

  for (const user of users) {
    if (forcePasswordUpdate) {
      await pool.query(`
        INSERT INTO icingaweb_user ("name", "active", "password_hash", "ctime", "mtime")
        VALUES ($1, $2, convert_to(crypt($3, gen_salt('bf')), 'UTF8'), NOW(), NOW())
        ON CONFLICT ("name") DO UPDATE
        SET "active" = EXCLUDED."active",
            "password_hash" = EXCLUDED."password_hash",
            "mtime" = NOW()
      `, [user.name, user.active, user.password]);
    } else {
      await pool.query(`
        INSERT INTO icingaweb_user ("name", "active", "password_hash", "ctime", "mtime")
        VALUES ($1, $2, convert_to(crypt($3, gen_salt('bf')), 'UTF8'), NOW(), NOW())
        ON CONFLICT ("name") DO NOTHING
      `, [user.name, user.active, user.password]);
    }

    if (user.admin) {
      await pool.query(`
        INSERT INTO icingaweb_group_membership ("group_name", "username", "ctime", "mtime")
        VALUES ('Administrators', $1, NOW(), NOW())
        ON CONFLICT ("group_name", "username") DO NOTHING
      `, [user.name]);
    }
  }

  logger.log?.(JSON.stringify({
    level: 'info',
    service: 'r-observe-api',
    msg: 'Initial users bootstrapped',
    users: users.map((user) => user.name),
    ts: new Date().toISOString(),
  }));

  return { users: users.length };
}

module.exports = { bootstrapInitialUsers, parseInitialUsers };
