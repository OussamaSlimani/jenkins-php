pipeline {
  agent any
  environment {
    SONARQUBE = 'sonarqube'
  }
  stages {
    stage('PHPStan Analysis') {
      steps {
        script {
          // Install PHPStan if not already installed
          sh 'composer require --dev phpstan/phpstan'
          // Run PHPStan analysis and save output to a file
          try {
            sh 'vendor/bin/phpstan analyze src -l 6 > phpstan_errors.txt'
          } catch (err) {
            // Catch any errors but continue the pipeline
            echo "PHPStan analysis encountered errors but continuing..."
          }
        }
      }
    }
    stage('SonarQube Analysis') {
      steps {
        script {
          def scannerHome = tool 'SonarQube Scanner 6.0.0.4432'
          withSonarQubeEnv('sonarqube') {
            sh """
            ${scannerHome}/bin/sonar-scanner \
              -Dsonar.projectKey=jenkins \
              -Dsonar.sources=src \
              -Dsonar.host.url=http://192.168.1.16:9000 \
              -Dsonar.token=sqp_1d7dfc63f3aa4bd51c66daa32f52a3d9a5d70436
            """
          }
        }
      }
    }
  
    stage("Quality Gate") {
      steps {
        script {
          try {
            waitForQualityGate abortPipeline: true
          } catch (err) {
            echo "Quality Gate check failed: ${err}"
          }
        }
      }
    }
  
  }
  post {
    always {
      echo 'Pipeline has finished.'
    }
  }
}
