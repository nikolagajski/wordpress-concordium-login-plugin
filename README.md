# Wordpress login plugin for Concordium

This plugin is for handles User Authentication against an Concordium server.
Warning! Before enabling the plugin you must have at least one authentication plugin enabled or you will lose all access to your site.

Also this plugin requires to install `sodium` and `grpc` PHP extensions.

To set up this plugin locally you will need to pull the code with:

`git clone https://github.com/nikolagajski/wordpress-concordium-login-plugin.git`

## PHP set up

If you have PHP 7.2 or higher install you need to add/enable `sodium` and `grpc` PHP extensions.
After that you can run the next commands.

`npm i` - initialize libraries

`npm run build` - for building Wordpress zip installer (PHP 7.2 or higher)

`npm run watch` - for watching changes in the JS when developing

## Docker set up

### Linux

Alternatively can be used docker-compose with npm and php included, see available commands in `Makefile`:
_Before build docker container please make sure you set correct USER_ID and GROUP_ID in .env file_

`make init` - initialize libraries

`make build` - for building Wordpress zip installer (PHP 7.2 or higher)

`make watch` - for watching changes in the JS when developing

### Windows

If you don't have `Makefile` set uo on Windows you can use direct docker commands.

`docker-compose run php-npm npm i` - initialize libraries

`docker-compose run php-npm npm run build` - for building Wordpress zip installer (PHP 7.2 or higher)

`docker-compose run php-npm npm run watch` - for watching changes in the JS when developing

## Installing and Set up

After running the build the install package will be created in the `dist` folder.

### Configuration

Warning! You must have at least one authentication plugin enabled before you set up and enable of this plugin
or you will lose all access to your site.

There are 2 server type options:

- `gRPC` - uses native protocol
- `JSON-RPC` - uses proxy protocol

Dependent on that select you will need to add the `gRPC` or `JSON-RPC` Concordium client hostname:

- `gRPC` - example.com:10000
- `JSON-RPC` - https://example.com:9095

The last setting is the Nonce Expiring interval to set up the expiration time for singed in.
The available formats can be found here [https://www.php.net/manual/en/dateinterval.format.php] 