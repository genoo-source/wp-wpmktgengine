#!/usr/bin/env bash

# Get needed versions from a
PLUGIN_CURRENT_VERSION=$(awk '/   Version/{print $NF}' wpmktgengine.php)
PLUGIN_NEXT_VERSION=$(echo $PLUGIN_CURRENT_VERSION | awk -F. -v OFS=. 'NF==1{print ++$NF}; NF>1{$NF=sprintf("%0*d", length($NF), ($NF+1)); print}')

read -p "Would you like to update plugin from $PLUGIN_CURRENT_VERSION to $PLUGIN_NEXT_VERSION ?" response
if [[ $response =~ ^(yes|y| ) ]] || [[ -z $response ]]; then
  echo "Updating to a new version..."
  # New version update
  # Replace versions with new version
  # - In main file
  sed -i "" "s/${PLUGIN_CURRENT_VERSION}/${PLUGIN_NEXT_VERSION}/g" wpmktgengine.php
  # - In readme file
  sed -i "" "s/Stable tag: ${PLUGIN_CURRENT_VERSION}/Stable tag: ${PLUGIN_NEXT_VERSION}/g" readme.txt
  # New tag and push
  git commit -am "Release: $PLUGIN_NEXT_VERSION"
  git tag -a $PLUGIN_NEXT_VERSION -m "Release: $PLUGIN_NEXT_VERSION"
  git push origin master --tags
  # All done, yay
  echo "Updated new version"
fi
