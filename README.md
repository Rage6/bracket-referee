# The Bracket Referee

## https://bracket-referee.herokuapp.com

<b>Client-side Languages</b>: Javscript, jQuery (3.3.1.min), JSON, CSS, SASS (3.5.5), HTML

<b>Server-side Languages</b>: PHP (7.1.19)

<b>DB Managment System</b>: MySQL

<b>Status</b>: <i>Complete</i>

<u><i>PURPOSE:</i></u>
<p>This is an exercise of my PHP skills and database design, and it's purpose is for any user to compete against friends and family during any common tournaments ("March Madness", FIFA World Cup, etc.). It was my first, independent venture into "server-side" coding from scratch... and I LOVED it! This app now allows the user to:</p>
<ul>
  <li>Create, read, update, or delete their own account</li>
  <li>Create, read, update, or delete their own group(s)</li>
  <li>Search for an existing group</li>
  <li>Join an existing group</li>
  <li>Submit one bracket of teams within each group</li>
  <li>View their score and the top 25 scores within each group</li>
  <li>Create, read, update, or delete their messages within a group(s) message board</li>
  <li>Create, read, update, or delete comments related to other messages within their group(s)</li>
</ul>

<u><i>CODE:</i></u>
<p>Several portions of this app's code are worth mentioning:</p>
<ol>
  <li>
    <b>Many-To-Many Relationships</b>: This app collects a large amount of tables within the overall database, so it's predictable that at least one many-to-many became involved. For example, a single "Group" can include many "Players", and vice versa.
  </li>
  <li>
    <b>JavaScript Object Notation (JSON)</b>: In order to easily identify and share the correct data with the user (w/o making unnecessary requests), JSON is used extensively as the user chooses their teams when filling out their bracket(s).
  </li>
  <li>
    <b>Password Encryption</b>: Upon doing research, I used PHP's more recent tool for encrypting the user's password: <i>password_hash()</i>. My previous training had only explained how to manually insert a "salt" before using the more basic <i>hash()</i> and a chosen algorithm, like MD5. The <i>password_hash()</i> seems to do it easier and more affectively.
  </li>
  <li>
    <b>Message Expiration After 30 Days</b>: To prevent the accumulation of old, unnecessary message posts, group messages older than 30 days are automatically delete every time a user enters that group's page. The expired messages are determined by comparing the current timestamp to the message's original timestamp. 
  </li>
  <li>
    <b>Hacker Prevention</b>: Several measures have been taken in order to prevent hacking attacks. They include:
    <ul>
      <li>
        <i>htmlentities()</i> is used to block SQL injection. If not, SQL could be maliciously used to see or modify the database.
      </li>
      <li>
        By using the <i>token-based authentication</i> method, tokens are created each a player logs in. It is then used to confirm that they are who they say they are, each and every request. Each token is randomly generated.
      </li>
      <li>
        To defend against <a href="https://en.wikipedia.org/wiki/Brute-force_attack#Countermeasures">"brute force attacks"</a>, users can make no more than 5 attempts at logging in, after which the attacked account is locked. To unlock it, the real user must reset their password using the <i>Forgot Your Password?</i> option on the index page. Their new, random password will be sent to their email address.
      </li>
     </ul>
  </li>
  <li>
    <b>Simple Mail Transfer Protocol (SMTP)</b>: Users can get access to their accounts in spite of forgetting their password by having a reset passwords emailed directly to their recorded email address. This is carried out by using a free, third-party email service (SendGrid) and a PHP package manager (Composer).
  </li>
  <li>
    <b>Private vs. Public</b>: Some users would undoubtedly want to draw in as many other users as possible, while others would want to limit their groups to only friends and family. The "Private/Public" setting that I added makes a group more or less selective of its members. It does this by:
    <ol>
      <li>
        Showing or hiding the group on the public list of "Available Groups"
      </li>
      <li>
        Including or excluding the group on whether the group can appear on the 'search tool' on player.php
      </li>
      <li>
        Adding a unique "key" to the "invite links", making it very difficult for non-members to enter without an invite.
      </li>
    </ol>
  </li>
  <li>
    <b>Invitation Link</b>: An "invitation link" is included in all groups and makes it easy for emailing/texting someone a URL that will take them directly to the desired group. Upon entering that URL, the user easily logs in or create an account (if they aren't logged in already), then they are sent to the desired group. If that group is in the 'PRIVATE setting, its link is only shown to the group's director, but the 'PUBLIC' setting show will show the link to all of its members.
  </li>
  <li>
    <b>Varying Tournament Structure</b>: Many single-elimination tournaments differ from the traditional structure. In particular <a href='https://en.wikipedia.org/wiki/Wild_card_(sports)'>"wildcard"</a> and <a href='https://en.wikipedia.org/wiki/Third_place_playoff'>"third-place playoffs"</a> games often occur. <u>Bracket Referee</u> is designed to accommodate for those.
  </li>
  <li>
    <b>Time-Sensitive Bracket Submission</b>: There is often a narrow time (between the announcement of the tournament's teams and the first game that takes place) in which a player can pick their predictions. Using things like PHP's <i>date()</i> and <i>date_default_timezone_set()</i>, this website will allow a player to submit their bracket at the last possible moment.
  </li>
  <li>
    <b>Administrative Center</b>: Manually adding new teams or updating each game's results is very inefficient and increases the chances that a mistake is made. To prevent this, the Administrative Center page (which is only accessible by the developer) can be used to easily add a new team, insert the correct teams on each game, and update each game's winner.
  </li>
</ol>
