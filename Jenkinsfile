pipeline {
    agent any

    stages {
        stage('Check Composer') {
            steps {
                script {
                    // Check if Composer is installed
                    def composerInstalled = sh(script: 'composer --version', returnStatus: true) == 0

                    if (composerInstalled) {
                        echo "Composer is installed."
                        // Optionally, output the Composer version
                        sh 'composer --version'
                    } else {
                        error "Composer is not installed. Please install Composer to proceed."
                    }
                }
            }
        }
    }
}
