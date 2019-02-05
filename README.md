# The Bracket Referee

## https://bracket-referee.herokuapp.com

<b>Languages</b>: PHP, Javscript (jQuery, JSON), CSS (SASS), HTML

<b>DB Managment System</b>: MySQL

<b>Status</b>: <i>Functional, not completed</i>

<u><i>PURPOSE:</i></u>
<p>This is an exercise of my PHP skills and database design, and it's purpose is for any user to compete against friends and family during any common tournaments ("March Madness", FIFA World Cup, etc.). It is my first, independent venture into "server-side" coding from scratch... and I'm LOVING it! Once completed, this app will allow the user to:</p>
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
    <b>JavaScript Object Notation (JSON)</b>: In order to easily identify and share the correct data) with the user (w/o making unnecessary requests), JSON is used extensively as the user chooses their teams when filling out their bracket(s).
  </li>
  <li>
    <b>Password Encryption</b>: Upon doing research, I used PHP's more recent tool for encrypting the user's password: <i>password_hash()</i>. My previous training had only explained how to manually insert a "salt" before using the more basic <i>hash()</i> and a chosen algorithm, like MD5. The <i>password_hash()</i> seems to do it easier and more affectively.
  </li>
  <li>
    <b>Hacker Prevention</b>: Several measures have been taken in order to prevent hacking attacks. They include:
    <ul>
      <li>
        <i>htmlentities()</i> is used to block SQL injection. If not, SQL could be maliciously used to see or modify the database.
      </li>
      <li>
        By using the <i>token-based authentication</i> method, tokens are created each session in order to confirm that they are who they say they are, each and every request. Each token is randomly generated using PHP's <i>random_bytes()</i>
      </li>
     </ul>
  </li>
  <li>
    <b>Simple Mail Transfer Protocol (SMTP)</b>: Users can get access to their accounts in spite of forgetting their password by having a reset passwords emailed directly to their recored email account. I did this by using a free, third-party email service (SendGrid) and a PHP package manager (Composer).
  </li>
  <li>
    <b>Private vs. Public</b>: Some users would undoubtedly want to draw in as many other users as possible, while others would want to limit their groups to only friends and family. The "Private/Public" setting that I added makes a group more or less selective. It does this by:
    <ol>
      <li>
        Shows or hides the group on the public list of "Available Groups"
      </li>
      <li>
        Includes or excludes the group on when using the group search tool on player.php
      </li>
      <li>
        Adds a unique "key" to the "invite links", making it difficult for non-members to enter without an invite.
      </li>
    </ol>
  </li>
  <li>
    <b>Invitation Link</b>: An "invitation link" is included in all groups (but only shown to the group's creator if the group is "private") and makes it easy for emailing/texting a direct route to a certain group. It is set up so that someone can easily login or create an account before entering the group.
  </li>
  <li>
    <b>Varying Tournament Structure</b>: Many single-elimination tournaments slightly differ from their traditional structure. In particular "wildcard" (a.k.a "games that fill out the first full round") and "third-place" (a.k.a "semi-finals that compete for third place") games often occur. This app is designed to adjust for those during team selection and scoring.
  </li>
</ol>
