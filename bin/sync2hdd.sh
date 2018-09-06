#!/bin/bash

SOURCE_DIR=/media/
TARGET_DIR=/mnt/data/

if [[ ! $(findmnt -M "${TARGET_DIR}") ]]; then
    echo "No external hard drive mounted. Cannot sync!"
    exit 2
fi

# sync to external hard drive (note: only adding new files, nothing gets deleted)
sudo rsync -a --times --exclude "*~" ${SOURCE_DIR} ${TARGET_DIR} 2>&1
if [ $? -ne 0 ]; then
  echo "could not sync, skipping clean up"
  exit 2
fi

# clean up
sudo find ${SOURCE_DIR} -mindepth 3 -type f -mtime +0 -delete 2>&1

# remove empty folders
sudo find ${SOURCE_DIR} -mindepth 3 -depth -empty -delete 2>&1

# TODO reboot?