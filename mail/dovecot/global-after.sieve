require ["fileinto", "mailbox"];

if anyof(
  header :contains "X-Spam" "Yes",
  header :contains "X-Spam-Status" "Yes"
) {
  fileinto :create "Spam";
  stop;
}