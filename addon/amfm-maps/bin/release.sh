# #!/bin/bash

# Check if a version number is provided
if [ -z "$1" ]; then
  echo "Error: No version number provided."
  echo "Usage: $0 <version>"
  exit 1
fi

VERSION=$1

# Create a new tag
git tag -a "$VERSION" -m "Release version $VERSION"

# Push the tag to the remote repository
git push origin "$VERSION"

# Create a release on GitHub
gh release create "$VERSION"