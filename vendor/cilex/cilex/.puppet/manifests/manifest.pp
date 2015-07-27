group { "puppet":
    ensure => "present",
}

node default {
    exec { "apt-get update":
      command => "/usr/bin/apt-get update"
    }

    package{["git", "python-setuptools", "wget", "make", "texlive-latex-recommended", "texlive-latex-extra", "texlive-fonts-recommended", "openjdk-6-jre", "php5-cli", "php5-xdebug"]:
        ensure  => present,
        require => Exec["apt-get update"]
    }

    exec { "sudo easy_install -U sphinx":
        command => "/usr/bin/sudo /usr/bin/easy_install -U sphinx",
        require => [ Package["python-setuptools"] ],
        timeout => 0
    }

    exec { "get-composer":
        command => "/usr/bin/wget -N http://getcomposer.org/composer.phar",
        require => Package["wget"],
        cwd     => "/vagrant"
    }

    exec { "composer-install":
        command => "/usr/bin/php composer.phar install --dev",
        require => Exec["get-composer"],
        cwd     => "/vagrant"
    }
}