# Wikimedia portal stats

* How many visits have the Biology portal?
* How many pages have the Free software portal?
* Etc.

## Try it

Go to [Wikimedia Foundation Labs / portal-stats](https://tools.wmflabs.org/portal-stats/).

## Installation

Get the source code:

    git clone --recursive git@github.com:valerio-bozzolan/Wikimedia-portal-stats.git

Copy the configuration template:

    cp config-example.php config.php

Do things in the configuration:

    editor config.php

Have fun!

## Usage

Fetch all the portals:

    ./fetch-portals.php


Fetch statistics (interrupt with ^C when you think that you are enough!):

    ./fetch-stats.php


Publish the data:

    # Publish data
    ./publish.php

Have fun with the website!

## License
Copyright (C) 2017  Valerio Bozzolan and contributors

This program is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.

