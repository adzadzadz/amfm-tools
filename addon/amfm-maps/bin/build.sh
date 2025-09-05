#!/bin/bash

# Check if a filename is provided
if [ -z "$1" ]; then
    echo "Usage: build.sh <filename>"
    exit 1
fi

# Set the filename and build directory
FILENAME="$1"
BUILD_DIR="build"

# Create the build directory if it doesn't exist
if [ ! -d "$BUILD_DIR" ]; then
    mkdir "$BUILD_DIR"
fi

# Archive the current directory using Git
git archive --format=zip -o "$BUILD_DIR/$FILENAME.zip" HEAD

echo "Archive created at $BUILD_DIR/$FILENAME.zip"