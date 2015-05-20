#
# author phil@elsondesigns.com
#
Vagrant.configure("2") do |config|
	# All Vagrant configuration is done here. The most common configuration
  	# options are documented and commented below. For a complete reference,
  	# please see the online documentation at vagrantup.com.
  	# Every Vagrant virtual environment requires a box to build off of.
  	# This is an ubuntu environment, needs to be changed to redhat at some point
  	config.vm.box = "ubuntu/trusty64"

  	# We are simply provisioning the environment via a shell script. 
  	# This includes apache, PHP, MySQL, vhosts and downloading and installed the latest DB
  	config.vm.provision :shell, :path => "bootstrap.sh"
  
  	# Current directory this of vagrant file is to be mounted on the guest machine
  	# in /var/www/b2c (same path which is in the vhosts)
  	config.vm.synced_folder ".", "/var/www", :mount_options => ["dmode=777","fmode=777"]

  	# Create a forwarded port mapping which allows access to a specific port
  	# within the machine from a port on the host machine. In the example below,
  	# accessing "localhost:8080" will access port 80 on the guest machine.
  	# Note if the site you're after isn't working correctly, update core_config_data 
  	# URL's to include 8080 in the URL.
  	# See .vagrant/bootstrap.sh for more information.
  	config.vm.network :forwarded_port, guest: 80, host: 8080

	#VM with 4GB of RAM
  	config.vm.provider :virtualbox do |vb|
  		#vb.customize 	["modifyvm", :id, "--cpuexecutioncap", "90"]
    	vb.customize 	["modifyvm", :id, "--memory", "4096"]
    	vb.customize 	["modifyvm", :id, "--cpus", "2"]
  	end
  
  	#Configuring the host vhosts
  	config.hostmanager.enabled = true
  	config.hostmanager.manage_host = true
  	config.hostmanager.ignore_private_ip = true
  	config.hostmanager.include_offline = true
  	config.vm.define 'sanitizer' do |node|
    	node.vm.hostname = 'sanitizer.dev'
    	node.hostmanager.aliases = %w(sanitizer.dev)
  	end

	#Vagrant triggers
        config.trigger.before :up do
           run "vagrant-transient/vagrant-transient.sh dc"
        end

        config.trigger.after :destroy do
           run "vagrant-transient/vagrant-transient.sh destroy"
        end

end
