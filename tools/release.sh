#!/bin/bash 

set -e


check=$(git symbolic-ref HEAD | cut -d / -f3)
if [ $check != "develop" ]; then
    echo "Must be on develop branch"
    exit -1
fi

# So that we can see un-committed stuff
git status

# Display list of recently released versions
git fetch --tags
git log --tags --simplify-by-decoration --pretty="format:%d - %cr" | head -n5

echo "Which version we are releasing: "
read version

function finish {
  git checkout develop
  git branch -d release/$version
}
trap finish EXIT

# Create temporary branch (local only)
git branch release/$version

# Find out previous version
prev_version=$(git log --tags --simplify-by-decoration --pretty="format:%d" | head -1 | grep -Eo '[0-9\.]+')

echo "Releasing $prev_version -> $version"

vimr CHANGELOG.md

# Compute diffs
git log --graph --pretty=format:'%Cred%h%Creset -%C(yellow)%d%Creset %s %Cgreen(%cr)%Creset' --abbrev-commit --date=relative $prev_version...

git log --pretty=full $prev_version... | grep '#[0-9]*' | sed 's/#\([0-9]*\)/\1/' | while read i; do
    echo '---------------------------------------------------------------------------------'
    ghi --color show $i | head -50
done

open "https://github.com/atk4/dsql/compare/$prev_version...develop"

# Update dependency versions
composer require atk4/core

composer update
phpunit --no-coverage

echo "Press enter to publish the release"
git commit -m "Added release notes for $version" CHANGELOG.md
merge_tag=$(git rev-parse HEAD)

git add composer.json
git commit -m "Set up stable dependencies for $version" CHANGELOG.md

git tag $version
git push origin release/$version
git push --tags

git checkout develop
git merge $merge_tag --no-edit
git push

# do we care about master branch? nah
