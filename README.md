# Steps to configure

## Install Docker
https://www.docker.com

## Build the image
From the project's root directory:  
`docker build -t reddit-help .`

## Run the script
From the project's root directory:  
`docker run -v $(pwd)/app:/app reddit-help php /app/main.php`

Once the script is run, a CSV named `csv.txt` will be created in the `app` directory.
