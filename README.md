# Caterpillars Count! #
Created by Aaron Plocharczyk for CaterpillarsCount! at UNC Chapel Hill.
All rights reserved.


- - - -


## GENERAL DIRECTORY ORGANIZATION ##
Working our way from back end to front end, the general flow of this system is as follows:

The "Keychain.php" file in the "resources" folder (found in "root -> php -> orm -> resources") gives us a "Keychain" object which allows us to easily connect to our database.

The class files in the "orm" folder (found in "root -> php -> orm") use this Keychain to connect to our database and provide the ability to construct objects that are easy to work with. Dealing with objects instead of connecting directly to our database keeps our code simple and understandable. It is important to note that these class files do not *themselves* create objects, but instead provide the *ability* to create objects. Think of these class files like factories. Factories are great, but somebody needs to *run* the factory if it's going to produce anything.

The API files in the "php" folder (found in "root -> php") *run* the ORM's class files to create objects that are then used to perform specific actions, like logging in to an account in the login.php file. Notice that these API files generally do NOT connect to our database directly. Instead, they just create objects using our ORM files and call methods with those objects. Dealing with objects and letting the ORM take care of updating the database accordingly makes the code in these files much shorter and much more human-readable.

The index.html files found within many of the folders in the root directory are our front-end pages. If these pages need to connect to our back end, they do so by making an ajax call to an API file in the "php" directory. You can provide arguments to the API file if needed. When the API file receives this call, it will perform a specific action and return the result back to the front-end page. Usually, the front-end page displays the result of this call to the user in some way. Upon logging a user in, for example, the signIn/index.html page redirects that user to one of their account pages to signal that they've been logged in.

Front-end pages draw their foundational "template" style and script from the "css" and "js" folders found in the root directory. Any style/script that relates more to a specific page than the site as a whole can be found within the HTML of that page itself by way of style and script tags.

Images are stored in the "images" folder in the root directory, and PDFs are stored in the "pdfs" folder in the root directory.

There are exceptions to these rules. It is easier for *some* API files to connect to the back end directly instead of through objects. The front-end dataDownload page is actually a PHP file mixed with HTML, and it gets large amounts of data from our database without needing to call an API file. The general flow of this system is not law, but it is good practice and is adhered to as much as possible.


## NOTABLE FILES AND FOLDERS ##
root -> index.html is the homepage of the website.

root -> composer.json draws in the code necessary to perform email functions in PHP with Carolina CloudApps.

root -> phpMyAdmin is your phpMyAdmin interface available at https://caterpillarscount.unc.edu/phpMyAdmin.

root -> php -> orm -> resources -> mailing.php provides "email" and "advancedEmail" functions that allow you to quickly and easily send emails with PHP, along with some other HTML-formatted email functions which are used exclusively for automatic regularly scheduled emails.

root -> php -> emails -> cron.php is connected to a cron job and regularly sends out HTML-formatted emails to our users. For more details on how this file works, read root -> php -> emails -> Instructions.txt.

root -> api -> users.php exists to stop users from creating accounts through the *old* Caterpillars Count! website. At some point in the process of creating an account through the old website, users are directed to the absolute URL "https://caterpillarscount.unc.edu/api/users.php", so we made that page into a notice that we have a new website that they should sign up through.

root -> old is a copy of the old website's git repository. This copy is not being used for anything. The original files for the old website are available at https://github.com/hurlbertlab/caterpillars-count. I, Aaron Plocharczyk, did not create the old website; I only created the new one. The old website was created by other developers for Caterpillars Count!
