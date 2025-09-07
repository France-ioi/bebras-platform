# Bebras Platform

This repository contains everything you need to run the *Bebras* Task Platform, historically developped for (and run at) [French Beaver Contest](http://castor-informatique.fr/).

## Prerequisites

You need:
- [Git](http://git-scm.com/)
- [Composer](https://getcomposer.org/)
- [Bower](http://bower.io/)

You also need a web server with PHP (>= 5.5) and a MySQL database.

## Installation

Clone the repository:

    git clone https://github.com/France-ioi/bebras-platform.git
    cd bebras-platform
    git submodule update --recursive --init

Copy `config_local_template.php` into `config_local.php`, and set the parameters for URLs and database.

Visit `dbv/index.php` with your web browser, login and passwords are those of the database.

Get composer dependencies:

    composer install

Make the directories `logs/` and `contestInterface/contests/` writable by PHP.

Get Bower dependencies: run `bower install` in both `contestInterface` and `teacherInterface`.

### Triggers

The database can optionally contain triggers writing in the different `*_history` tables. To install them, run `php commonFramework/modelsManager/triggers.php`. Note that before going into production, you must remove by hand the triggers in `team`, `group` and `question`.

### Additions

If you want to test the platform, run `sampleDatabase/database_content.sql` in your database.

For translation and country-specific features, please refer to the [documentation](teacherInterface/i18n/README.md).

For installation on [AWS](https://aws.amazon.com/), see [README.AWS.md](README.AWS.md).

### Update

    git pull
    git submodule update --recursive
    composer install
    cd contestInterface && bower update && cd ..
    cd teacherInterface && bower update && cd ..

## Initial configuration

*Create an admin user:* go to `/teacherInterface/`, register
through the link “Register!”. For the first user, you need to
validate it manually through the database. A record should have been
created in the “user” table, and you need to set the fields
“validated” and “isAdmin” to 1. You can then log in on the
`/teacherInterface/index.php` page.

*Generate contests:* contests need to be “generated”, which means compiling all of the
questions into a single HTML file, a CSS file, a JS file and a PHP
file. You do that as an administrator in the “Contest” tab by selecting a
contest in the first grid, and clicking on “Regenerate selected contest”.
Make sure PHP has read/write access to the contests folder,
where it will create a sub-folder for each contest that you
generate. The interface doesn't say anything if there is an error, so
you have to check that the folder has been created.

Once contests have been generated, you can try them as a contestant:
go to contestInterface/index_en.html in the root folder. It should look exactly the same
as [http://concours.castor-informatique.fr](http://concours.castor-informatique.fr),
and you can click on any contest that has been generated.

Note that if it were a production server, you would want to put an
`.htaccess` file or equivalent in the questions folder, otherwise people
will be able to access questions before the contest. You will need to
input the corresponding login/password if you want to preview
questions from the admin interface.

Teachers use a reduced version of the admin interface, so you might
want to create a different user, that is not an admin to see how it
looks. For that, register a new user through the interface, and set
“validated” to 1 from the interface of the admin user. Teachers can
create groups, and get a password that students input to participate
in the corresponding contest (this is explained in detail in the
“Explanations” tab). When a contestant participates through a group,
there are two extra steps compared to the public contest: he/she is
asked for the number of students doing the contest as a team (1 or 2),
then each student is prompted for her firstname, lastname and gender.
Other than that, everything works the same way as public contests.

## Common problems

- *Something doesn't load.* Check out the web console to know why.
  If a contest doesn't generate, verify PHP can write into the `contestInterface/contests/` folder.
- *I get a 404 error.* Check your URLs. Be sure not to mix server name synonyms (like `127.0.0.1` and `localhost`)
  as some web browsers block third party calls. Verify your contest has been generated.

# TODO

- Prepare gulp files for contestInterface and teacherInterface to compile all needed dependencies into one big uglified JS (see [this example](https://github.com/France-ioi/fioi-editor/blob/master/gulpfile.js)) and document it into the installation.
- Tests!
- Make task directory configurable (now it's certainly hardcoded as bebras-tasks).
- Tracking/ has unmet external dependencies (should probably link to external CDNs).
