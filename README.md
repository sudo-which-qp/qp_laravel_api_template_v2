## QP Laravel API Template

<p>So this is me trying to make a laravel template, if you have any issues please let me know.</p>

<p>This is mostly focused on building API's if you make use of laravel for building livewire and adding React or Vue to your laravel project, I don't think this is for you, but you can modify the code base to suit you and what you want.</p>

## Compose Issues (Most of this issues came from windows PC)

1. On windows not common on Macs, the expose server from the docker container might not load up or work. what you just have to do is run this command "docker network create application"

2. If you run the build for the first time after cloning the project and you get an error when trying to run docker-compose again, make sure that the "data/" is in the dockerignore file, if you need all the files in the data you can only ignore "data/mysql.sock".

3. Make sure to use "docker-compose up --build", so everything can run well, you can to enter the docker sh of the container to run the php artisan migrate and other artisan commend

<hr>

<p>But I built a CLI in Go to help me clone the project and make it for 
any project I want. The CLI will help you get everything set, instead of cloning the project each time from GitHub.</p>

<p> Here is the repo to the CLI <a href='https://github.com/sudo-which-qp/qp_laravel_cli.git'><QP></QP></a></p>
