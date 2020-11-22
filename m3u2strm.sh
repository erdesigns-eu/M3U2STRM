#!/bin/bash

# M3U 2 STRM - v1.0.0 (November 2020)
# Coded by: ERDesigns - Ernst Reidinga (c) 2020

trap 'printf "\n";stop;exit 1;clear;' 2


dependencies () {
	# Check if PHP is installed
	command -v php > /dev/null 2>&1 || { 
		echo >&2 "PHP is required! Please install PHP first and try again."; 
		exit 1; 
	}
	# Check if CURL is installed
	command -v curl > /dev/null 2>&1 || { 
		echo >&2 "CURL is required! Please install CURL first and try again."; 
		exit 1; 
	}
}

banner () {
	printf "\e[1;34m                                                                          \e[0m\n"
	printf "\e[1;34m ███╗   ███╗██████╗ ██╗   ██╗██████╗ ███████╗████████╗██████╗ ███╗   ███╗ \e[0m\n"
	printf "\e[1;34m ████╗ ████║╚════██╗██║   ██║╚════██╗██╔════╝╚══██╔══╝██╔══██╗████╗ ████║ \e[0m\n"
	printf "\e[1;34m ██╔████╔██║ █████╔╝██║   ██║ █████╔╝███████╗   ██║   ██████╔╝██╔████╔██║ \e[0m\n"
	printf "\e[1;34m ██║╚██╔╝██║ ╚═══██╗██║   ██║██╔═══╝ ╚════██║   ██║   ██╔══██╗██║╚██╔╝██║ \e[0m\n"
	printf "\e[1;34m ██║ ╚═╝ ██║██████╔╝╚██████╔╝███████╗███████║   ██║   ██║  ██║██║ ╚═╝ ██║ \e[0m\n"
	printf "\e[1;34m ╚═╝     ╚═╝╚═════╝  ╚═════╝ ╚══════╝╚══════╝   ╚═╝   ╚═╝  ╚═╝╚═╝     ╚═╝ \e[0m\n"
	printf "\n"
	printf "\e[1;34m                .:.:.\e[0m\e[1;94m By Ernst Reidinga - ERDesigns \e[0m\e[1;34m.:.:.\e[0m\n"
	printf "\n"	
}

menu () {
	printf "\e[1;34m [\e[0m\e[1;31m01\e[0m\e[1;34m]\e[0m\e[1;94m Convert local M3U file\e[0m\n"
	printf "\e[1;34m [\e[0m\e[1;31m02\e[0m\e[1;34m]\e[0m\e[1;94m Convert remote M3U file\e[0m\n"
	printf "\e[1;34m [\e[0m\e[1;31m03\e[0m\e[1;34m]\e[0m\e[1;94m Command Line options\e[0m\n"
	printf "\n"
	printf "\e[1;34m ------------------------------------------------------------------------------\n"
	printf "\e[1;34m [\e[0m\e[1;31m99\e[0m\e[1;34m]\e[0m\e[1;31m Exit\e[0m\n"

	# Read selection
	read -p $'\n\e[1;34m [\e[0m\e[1;91m*\e[0m\e[1;34m] Enter your selection: \e[0m' option

	# Read filename / URL
	if [[ $option == 1 || $option == 01 ]]; then
		mode="LOCAL"
		read -p $'\e[1;34m [\e[0m\e[1;91m*\e[0m\e[1;34m] Local M3U filename: \e[0m' filename
		filename=(${filename[@]//\'/})
		if [[ ! -e $filename ]]; then
			printf "\e[1;34m [!]\e[31m File DOES NOT exist!\e[0m\n"
			sleep 1
			clear
			banner
			menu
		fi
	elif [[ $option == 2 || $option == 02 ]]; then
		mode="REMOTE"
		read -p $'\e[1;34m [\e[0m\e[1;91m*\e[0m\e[1;34m] Remote M3U URL: \e[0m' filename
		regex='^(https?|ftp|file)://[-A-Za-z0-9\+&@#/%?=~_|!:,.;]*[-A-Za-z0-9\+&@#/%=~_|]\.[-A-Za-z0-9\+&@#/%?=~_|!:,.;]*[-A-Za-z0-9\+&@#/%=~_|]$'
		if [[ ! $filename =~ $regex ]]; then
			printf "\e[1;34m [!]\e[31m Invalid URL!\e[0m\n"
			sleep 1
			clear
			banner
			menu
		fi
	fi

	# Read output directory
	if [[ $option == 1 || $option == 01 || $option == 2 || $option == 02 ]]; then
		read -p $'\e[1;34m [\e[0m\e[1;91m*\e[0m\e[1;34m] Output directory: \e[0m' directory
		if [[ $directory == "" ]]; then
			printf "\e[1;34m [!]\e[31m Please enter a valid directory!\e[0m\n"
			clear
			banner
			menu
		fi
		start
	fi

	# Command Line options
	if [[ $option == 3 || $option == 03 ]]; then
		printf "\n"
		printf "\e[1;34m This script takes 2 parameters, you can run this script from the CRON with these parameters: \n"
		printf "\n"
		printf "\e[1;34m [\e[0m\e[1;31m Option 1\e[0m\e[1;34m]\e[0m\e[1;94m M3U filename or URL\e[0m\n"
		printf "\e[1;34m [\e[0m\e[1;31m Option 2\e[0m\e[1;34m]\e[0m\e[1;94m Output directory\e[0m\n"
		printf "\n"
		printf "\e[1;34m [\e[0m\e[1;31m Example:\e[0m\e[1;34m]\e[0m\e[1;94m sudo bash m3u2strm.sh http://my-provider.com/get.php?username=abc&password=def&type=m3u_plus /home/username/desktop/strm \e[0m\n"
	elif [[ $option == 99 ]]; then
		# User wants to exit
		exit 1
	else
		# No valid input!
		printf "\e[1;34m [!]\e[31m Invalid selection!\e[0m\n"
		sleep 1
		clear
		banner
		menu
	fi

}

start () {
	printf "\n"
	printf "\e[1;34m Please wait while converting the M3U to STRM files. \n"
	currentdir=$(dirname "$0")
	php -f "$currentdir/m3u2strm.php" filename=$filename directory=$directory > "$currentdir/log.log"
	wait
	printf "\e[1;34m All done! \n"
	sleep 20
}

stop () {
	PHP=$(ps aux | grep -o "php" | head -n1)
	# Kill PHP
	if [[ $PHP == *'php'* ]]; then
		pkill -f -2 php > /dev/null 2>&1
		killall -2 php > /dev/null 2>&1
	fi
}

if [[ ! $1 == "" && ! $2 == "" ]]; then
	filename="$1"
	directory="$2"
	start
else
	clear
	banner
	menu
fi
