# -*- mode: ruby -*-
# vi: set ft=ruby :

require 'json'
require 'yaml'

configFile = File.expand_path("./config.yaml")
require_relative 'setup.rb'

Vagrant.configure(2) do |config|

  config.vm.network "private_network", ip: "192.168.7.7"

  config.vm.synced_folder ".", "/var/www/html/",
        owner: "vagrant",
        group: "www-data",
        mount_options: ["dmode=775,fmode=664"]

  Build.configure(config, YAML::load(File.read(configFile)))
end
