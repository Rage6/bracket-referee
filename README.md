# The Bracket Referee

## https://bracket-referee.herokuapp.com

<b>Languages</b>: PHP, jQuery, Javscript, SASS, CSS, HTML

<b>DB Managment System</b>: MySQL

<b>Database</b>: ClearDB

<b>Status</b>: <i>Under construction</i>

<u><i>PURPOSE:</i></u>
<p>This is an exercise of my PHP and database design, and it's purpose is for any user to compete against friends and family during any common tournaments ("March Madness", FIFA World Cup, etc.). It is my first, independent venture into "server-side" coding from scratch... and I'm LOVING it! Once completed, this app will allow the user to:</p>
<ul>
  <li>Create, read, update, or delete their own account</li>
  <li>Create, read, update, or delete their own group(s)</li>
  <li>Search for an existing group</li>
  <li>Join an existing group</li>
  <li>Submit one bracket of teams within each group</li>
  <li>View their score and the top 25 scores within each group</li>
</ul>

<u><i>CODE:</i></u>
<p>Several portions of this app's code are worth mentioning:</p>
<ol>
  <li>
    <b>Many-To-Many Relationships</b>: This app collects a large amount of tables within the overall database, so it's predictable that at least one many-to-many became involved. For example, a single "Group" can include many "Players", and vice versa. This demonstrates that I can write
  </li>
  <li>
    <b>Password Encryption</b>: Upon doing research, I used PHP's more recent tool for encrypting the user's password: <i>password_hash()</i>. My previous training had only explained how to manually insert a "salt" before using the more basic <i>hash()</i> and a chosen algorithm, like MD5. The <i>password_hash()</i> seems to do it easier and more affectively.
  </li>
  <li>
    <b>Hacker Prevention</b>: In order to prevent SQL injection, <i>htmlentities()</i> is used whenever a user submits any 'text' data. If not, SQL could be maliciously used to see or modify the database.
  </li>
  <li>
    <b>Database Security</b>: You may have noticed that this database's password is displayed on the PDO. DON'T WORRY! As soon as I complete this and deploy it to the public, I plan on changing the displayed password and ensure that its new password is not displayed on GitHub. I would hide it sooner, but I cannot seem to find a way to hide a certain line of code, only entire files.
  </li>
</ol>
