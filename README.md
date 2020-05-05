# ADFF
Advanced Duplicate File Finder

# Introduction

Different directories can be analysed by ADFF. Metadata for all files will be stored in the database to later search for any duplicates. Duplicates will be found across directories offering the possibility to mount different shares to a servers and find duplicates across all shares.

The tool makes use of Python, MongoDB, PHP, HTML, Bootstrap, Angular

# Installation

- Install a Ubuntu 18.04.4 LTS Server with Apache2 webserver and PHP
- Install MongoDB
- Install Python3 and pymongo
- Clone the git repository to /home/yourname
- Create the symlink in /var/www/html/adff point to /home/yourname/adff/webapp
- Rename the example_config.ini to config.ini
- Make /home/yourname/adff/adff.py executable

# Usage

- Go to your browser: http://yourhostname/adff
- Add the directories you would like to analyse one by one into the textfield and click "Add".
Make sure this is a valid directory. It will not be checked.
- Click on "Crawl" to tell the program to crawl that Directory
- Execute /home/yourname/adff/adff.py
- Execute it again and again until is says "Nothing left to do"
- Now go back to the browser and refresh the page
- You can scroll down and see your duplicates
- If you want to crawl again, use the "Clear all data" button and then start again

# Configuration

```
[database]
url = localhost
db = adff

[runtime]
job_count = 100
job_wait = 0.5
```

| Section | Key | Description |
| ---- | --- | --- |
| database | url | specifies the connection string to the Database |
| database | db | specifies the database name. (please do not change it as it is hard-coded in webapp/rest.php) |
| runtime | job_count | specifies the number of direcotries that will be analysed during one run of the script. You may keep this low for testing, but then change it according to your needs. The idea is to run this as a cron job and limit the time the script will run |
| runtime | job_wait | specifies the seconds that the script will wait after each directory. This can be used to lower the performance impact of the crawl |

# Database (MongoDB)

In this section we can see the database collections and the attributes used.

## directory

| Attribute | Description |
| --------- | ----------- |
| _id | automatic ID field |
| name | directory path |

## crawl

| Attribute | Description |
| --------- | ----------- |
| _id | automatic ID field |
| name | directory path |
| created | date and time of creation |
| status | defines the status of the entry, (new, ongoing, done) |
| crawl_id | specifies the top-level crawl |
| parent_id | specifies the parent crawl |
| started | date and time this crawl was started |
| finished | date and time this crawl was finished |

## file

| Attribute | Description |
| --------- | ----------- |
| _id | automatic ID field |
| crawl_id | top-level crawl |
| parent_id | parent crawl |
| name | filename |
| path | complete file path |
| size | size of the file in bytes |
| last_modified | last modified date and time |
| checksum_MD5 | MD5 checksum of the file content |
