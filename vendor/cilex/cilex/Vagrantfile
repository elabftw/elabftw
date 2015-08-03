# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant::Config.run do |config|
  config.vm.box     = "precise64"
  config.vm.box_url = "http://files.vagrantup.com/precise64.box"

  config.vm.provision :puppet do |puppet|
     puppet.manifests_path = ".puppet/manifests"
     puppet.manifest_file  = "manifest.pp"
     puppet.options        = [ '--verbose' ]
  end
end
