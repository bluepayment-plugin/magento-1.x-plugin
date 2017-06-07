#!/bin/bash
rsync -av --exclude=".*" --exclude="*.zip"  --exclude="*.pdf" --exclude="*.docx" --exclude="*.sh" . bluepayment
cd bluepayment
zip -ur bluepayment.zip .