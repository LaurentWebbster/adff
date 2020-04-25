# ADFF
Advanced Duplicate File Finder

# What will it do?
Different directories can be analysed by ADFF. Metadata for all files will be stored in the database to later search for any duplicates. Duplicates will be found across directories offering the possibility to mount different shares to a servers and find duplicates across all shares.

# Technical Implementation
- Python
- MongoDB

# How it works
A Python script analyses a given set of paths and stores metadata to all files found into MongoDB. Later it should be possible to analyse if there are any duplicates across the complete database
