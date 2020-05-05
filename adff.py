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
import time

def checkFinished(id):
    data = db.crawl.find({"parent_id": id})
    done = True
    for line in data:
        if line['status'] != "done":
            done = False
    if done:
        db.crawl.update({"_id": id}, {"$set": {"status": "done", "finished": datetime.utcnow()}})
        data = db.crawl.find_one({"_id": id})
        if "parent_id" in data:
            checkFinished(data["parent_id"])

def storeDirForCrawl(path, crawl_id, parent_id):
    attributes = {
        'name' : path,
        'created' : datetime.utcnow(),
        'status' : 'new',
        'crawl_id' : crawl_id,
        'parent_id' : parent_id
    }
    db.crawl.insert_one(attributes)

def storeMetaData(filepath, crawl_id, parent_id):
    attributes = {
        'crawl_id' : crawl_id,
        'parent_id' : parent_id,
        'name' : os.path.basename(filepath),
        'path' : filepath,
        'size' : os.path.getsize(filepath),
        'last_modified' : getDatetime(os.path.getmtime(filepath)),
        'checksum_MD5' : getChecksumMD5(filepath),
        }
    db.file.insert_one(attributes)
    global file_count
    file_count = file_count + 1


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
job_count = int(config['runtime']['job_count'])

while job_count > 0:
    print("Job_count: " + str(job_count))
    crawl = db.crawl.find({"status": "new"}).sort([("crawl_id", -1), ("created", 1)]).limit(1)
    nothing_to_do = True
    for crawl_data in crawl:
        nothing_to_do = False
        db.crawl.update_one({ '_id': crawl_data['_id'] }, { "$set": { 'status': 'ongoing', 'started': datetime.utcnow() } })
        path = crawl_data['name']
        print("Analysing:", path)

        #parent = dataset['_id']
        crawl_id = crawl_data.get("crawl_id", crawl_data["_id"])
        parent_id = crawl_data["_id"]

        onlyfiles = True
        for root, dirs, files in os.walk(path):
            for file in files:
                storeMetaData(os.path.join(root, file), crawl_id, parent_id)
            for dir in dirs:
                onlyfiles = False
                storeDirForCrawl(os.path.join(root, dir), crawl_id, parent_id)
            break
        #check for done
        if onlyfiles:
            print("Only files in this directory:", path)
            db.crawl.update_one({ '_id': crawl_data['_id'] }, { "$set": { 'status': 'done', 'finished': datetime.utcnow() } })
            # complete upper directories
            if "parent_id" in crawl_data: checkFinished(crawl_data["parent_id"])
        pass
        print()
        if "job_wait" in config['runtime']:
            time.sleep(float(config['runtime']['job_wait']))
    if nothing_to_do:
        print()
        print("Nothing left to do")
        job_count = 1
    job_count -= 1

print()
print('Number of files analysed: ' + str(file_count))

print('### Finish ###')
