#!/bin/bash

set -e

# Create release directory
mkdir ${WORKSPACE}/release || echo "Folder already exists"
cd ${WORKSPACE}/release

# Remove old artifacts
rm ${WORKSPACE}/*.zip || echo "No ZIP artifacts to remove"

# Copy contents
rsync -r --delete \
  --copy-links \
  --safe-links \
  --exclude=.* \
  --exclude=/src/files/* \
  --exclude=/src/webroot/files/* \
  --exclude=/src/log/* \
  --exclude=/tests \
  --exclude=/tmp \
  --exclude=/src/webroot/dev \
  --exclude /solr \
  --exclude /release \
  --exclude /supra7 \
  ${WORKSPACE}/ .

rm -rf ./tests
rm -rf ./tmp
rm -rf ./src/webroot/dev

# Replace INI file
cp ./src/conf/supra.release.ini ./src/conf/supra.ini.example
rm ./src/conf/supra*.ini

# Register version number
echo ${project_version}.${BUILD_NUMBER} > ./VERSION

cd src/webroot/
find ./cms/ -name "*.css.less" -exec php cms/lib/supra/combo/combo_pregenerate.php {} \; 
find ./cms/ -name "*.css.less" -delete

if [ -d "cms-local" ]
then
	find ./cms-local/ -name "*.css.less" -exec php cms/lib/supra/combo/combo_pregenerate.php {} \; 
	find ./cms-local/ -name "*.css.less" -delete
fi

find ./ -name "*.css.less" -exec php ./cms/lib/supra/combo/combo_pregenerate.php {} \;
find ./ -name "*.css.less" -delete

cd ${WORKSPACE}/release

zip -r ${WORKSPACE}/${JOB_NAME/%-release}.web.${project_version}.${BUILD_NUMBER}.zip *