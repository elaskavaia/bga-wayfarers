# bga-wayfarers

This is a Board Game Arena (BGA) implementation of the game "Wayfarers". It uses a custom template with minimal Dojo dependencies, featuring TypeScript for client-side code and PHP for server-side game logic.

Warning: Not for Beginners!

It uses typescript and possible to use scss

To use you need tsc (typescript), which will be installed by npm, which is part of nodejs

- Install nodejs (search web on how to install for your platform), i.e. sudo apt-get install nodejs
- Type "npm i" in the project directory - it will install tsc and scss compilers

To use in vscode:

- Install SFTP and configure, see https://en.doc.boardgamearena.com/Setting_up_BGA_Development_environment_using_VSCode

Run the following command generate stuff

- npm run build

Material.php is partially generated file, to run just generation of this

- npm run genmat

Run tests (also defined as vscode tasks)

- APP_GAMEMODULE_PATH=${userHome}/git/bga-sharedcode/misc/ phpunit --display-deprecations --display-warnings --bootstrap ./phptests/\_autoload.php
