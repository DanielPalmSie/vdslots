#!/usr/bin/env bash

npm run build-css && \
cp ./vs-games-mobile.js ./../../../phive/js/games/index.js && \
cp ./games.css ./../games.css && \
echo "Done!"
