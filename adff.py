#!/usr/bin/python3

######################################
# Advanced Duplicate File Finder     #
# Author: Laurent Webbster           #
# Platform: Ubuntu Linux 18.04.4 LTS #
######################################

import configparser
import hashlib
import os
from datetime import datetime
from pymongo import MongoClient


def analyse(path):
    for root, dirs, files in os.walk(path):
        for file in files:
            storeMetaData(os.path.join(root, file))

def storeMetaData(filepath):
    att = getAllFileAttributes(filepath)
    db.files.insert_one(att)
    global file_count
    file_count = file_count + 1

def getAllFileAttributes(file):
    attributes = {
        'name' : os.path.basename(file),
        'path' : file,
        'size' : os.path.getsize(file),
        'last_modified' : getDatetime(os.path.getmtime(file)),
        'checksum_MD5' : getChecksumMD5(file)
        }
    return attributes

def getChecksumMD5(file):
    hasher = hashlib.md5()
    with open(file, 'rb') as open_file:
        hasher.update(open_file.read())
    return hasher.hexdigest()

def getDatetime(timestamp):
    return datetime.fromtimestamp(timestamp)

### MAIN ###

print('### Start ###')
file_count = 0

config = configparser.ConfigParser(allow_no_value=True)
config.optionxform=str
config.read("config.ini")

client = MongoClient(config['database']['url'])
db = client[config['database']['db']]

for path in config['paths']:
    analyse(path)

print('Number of files analysed: ' + str(file_count))

print('### Finish ###')
