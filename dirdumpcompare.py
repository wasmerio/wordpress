#!/usr/bin/env python3

import urllib.request
import time
import sys
from typing import Dict, Optional, Tuple

BASE_URL = "http://localhost:8080"
# FETCH_URL = "http://wordpress-with-db.local:9080"

# URL to fetch the directory dump
FETCH_URL = BASE_URL
DUMP_URL = BASE_URL + "/dump-fs.php"

# Time interval (in seconds) between fetches
INTERVAL = 1
# File to store the last dump
DUMP_FILE = "last_dump.txt"

class DumpFetchError(Exception):
    """Custom exception raised when fetching the dump fails."""
    pass

type MetaData = Tuple[int, int, int, int]
type FileMap = Dict[str, MetaData]
type InstanceId = str

def fetch_dump() -> Tuple[FileMap, InstanceId]:
    """
    Fetches the dump from the webserver and returns the raw content and a dictionary
    mapping file paths to their metadata tuple: (ctime, mtime, atime, size).

    Raises:
        DumpFetchError: If fetching the dump fails.
        ValueError: If a line in the dump is not in the expected format.
    """
    try:
        with urllib.request.urlopen(DUMP_URL) as response:
            instance_id = response.getheader("x-edge-instance-id")
            content = response.read().decode("utf-8")
    except Exception as e:
        raise DumpFetchError(f"Error fetching dump: {e}")

    dump = {}
    lines = content.strip().splitlines()
    for line in lines:
        if not line.strip():
            continue
        parts = line.split(",")
        if len(parts) != 5:
            raise ValueError(f"Unexpected line format: {line}")
        file_path = parts[0].strip()
        try:
            ctime = int(parts[1].strip())
            mtime = int(parts[2].strip())
            atime = int(parts[3].strip())
            size  = int(parts[4].strip())
        except Exception as e:
            raise ValueError(f"Error parsing line: {line}. Exception: {e}")
        dump[file_path] = (ctime, mtime, atime, size)
    return dump, instance_id

def print_metadata(metadata):
    ctime, mtime, atime, size = metadata
    return f"ctime={ctime},mtime={mtime},atime={atime},size={size}"

def main():
    previous_dump = {}
    instance: Optional[str] = None

    while True:
        print('Fetching dump...')
        current_dump, instid = fetch_dump()
        if instance is None:
            instance = instid
        elif instance != instid:
            raise ValueError(f"Instance ID changed: {instance} -> {instid}")

        # Compare the current dump with the previous dump
        for file_path, metadata in current_dump.items():
            if file_path in previous_dump:
                prev_metadata = previous_dump[file_path]
                # ignore atime
                (prev_ctime, prev_mtime, _, prev_size) = prev_metadata
                (cur_ctime, cur_mtime, _, cur_size) = metadata

                if prev_ctime != cur_ctime or prev_mtime != cur_mtime or prev_size != cur_size:
                    print(f"WARNING: File info changed for '{file_path}'.")
                    print(f"Previous: {print_metadata(prev_metadata)}")
                    print(f"Current:  {print_metadata(metadata)}")
            else:
                print(f"New file detected: {file_path} - {print_metadata(metadata)}")

        # Check for removed files
        for file_path in previous_dump:
            if file_path not in current_dump:
                print(f"File removed: {file_path}")

        previous_dump = current_dump

        print('Fetching page...')
        with urllib.request.urlopen(FETCH_URL) as response:
            instid = response.getheader("x-edge-instance-id")
            if instance != instid:
                raise ValueError(f"Instance ID changed: {instance} -> {instid}")

            response.read().decode("utf-8")
        time.sleep(INTERVAL)

if __name__ == "__main__":
    main()
