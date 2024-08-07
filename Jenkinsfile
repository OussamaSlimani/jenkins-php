pipeline {
    agent any

    environment {
        DOCKERHUB_CREDENTIALS = credentials('Dockerhub')
        DOCKER_CONTENT_TRUST = '0'
    }

    stages {

        stage('PHPStan Analysis') {
            steps {
                script {
                    echo 'Preparing environment for PHPStan...'
                    sh 'composer require --dev phpstan/phpstan'
                    sh 'mkdir -p tmp-phpstan/cache'
                    sh 'chmod -R 777 tmp-phpstan'
                    writeFile file: 'phpstan.neon', text: '''
                    parameters:
                        tmpDir: tmp-phpstan/cache
                    '''
                    echo 'Running PHPStan Analysis...'
                    try {
                        sh 'vendor/bin/phpstan analyse -l 1 src/'
                        echo 'PHPStan Analysis completed successfully.'
                    } catch (err) {
                        echo "PHPStan analysis encountered errors: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "PHPStan analysis failed."
                    }
                    sh 'cp metrics.php src/'
                }
            }
        }

        stage('SonarQube Analysis') {
            steps {
                script {
                    echo 'Running SonarQube Analysis...'
                    try {
                        def scannerHome = tool name: 'SonarQube Scanner 6.0.0.4432'
                        withSonarQubeEnv('sonarqube') {
                            sh "${scannerHome}/bin/sonar-scanner"
                        }
                        echo 'SonarQube Analysis completed successfully.'
                    } catch (err) {
                        echo "SonarQube analysis encountered errors: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "SonarQube analysis failed."
                    }
                }
            }
        }
        
        stage('Quality Gate') {
            steps {
                script {
                    echo 'Waiting for Quality Gate...'
                    try {
                        timeout(time: 2, unit: 'MINUTES') {
                            waitForQualityGate abortPipeline: true
                            echo 'Quality Gate passed.'
                        }
                    } catch (err) {
                        echo "Quality Gate check failed: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "Quality Gate check failed."
                    }
                }
            }
        }

        stage('Lint Dockerfile hadolint') {
            steps {
                script {
                    echo 'Linting Dockerfile...'
                    try {
                        def hadolintOutput = sh(returnStdout: true, script: 'hadolint --config hadolint.yaml Dockerfile || true').trim()
                        if (hadolintOutput) {
                            error "Dockerfile linting failed:\n${hadolintOutput}"
                        } else {
                            echo 'Dockerfile linting passed.'
                        }
                    } catch (err) {
                        echo "Error during Dockerfile linting: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "Dockerfile linting failed."
                    }
                }
            }
        }

        stage('Create .env File') {
            steps {
                script {
                    echo 'Creating .env File...'
                    try {
                        withCredentials([string(credentialsId: 'db_credentials', variable: 'DB_CREDENTIALS')]) {
                            def envVariables = DB_CREDENTIALS.split(' ')
                            def envContent = envVariables.join('\n')
                            writeFile file: 'src/.env', text: envContent
                        }
                        echo '.env File created successfully.'
                    } catch (err) {
                        echo "Error creating .env File: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "Failed to create .env File."
                    }
                }
            }
        }

        stage('Build Docker Image') {
            steps {
                script {
                    def imageExists = sh(script: "docker images -q flare-bank:testing", returnStdout: true).trim()
                    if (imageExists) {
                        def containerIds = sh(script: "docker ps -a -q --filter ancestor=flare-bank:testing", returnStdout: true).trim()
                        if (containerIds) {
                            sh "docker stop ${containerIds}"
                            sh "docker rm ${containerIds}"
                        }
                        sh "docker rmi -f flare-bank:testing"
                    }
                    sh "docker build -t flare-bank:testing ."
                }
            }
        }

        stage('Dockle Docker Image Test') {
            steps {
                script {
                    sh "dockle flare-bank:testing || true"
                }
            }
        }

        stage('Test Security Trivy') {
            steps {
                script {
                    sh "trivy image --severity CRITICAL flare-bank:testing || true"
                }
            }
        }

        stage('Dockerhub Login') {
            steps {
                sh 'echo $DOCKERHUB_CREDENTIALS_PSW | docker login -u $DOCKERHUB_CREDENTIALS_USR --password-stdin'
            }
        }

        stage('Dockerhub Push') {
            steps {
                script {
                    sh "docker tag flare-bank:testing oussamaslimani2001/flare-bank:testing"
                    sh "docker push oussamaslimani2001/flare-bank:testing"
                }
            }
        }
        
       stage('Deployment') {
            steps {
                script {
                    echo 'Start deploying'
                    try {
                        echo "Deleting and recreating Minikube..."
                        sh 'minikube delete || true'
                        sh 'minikube start --driver=docker'
                        sh 'eval $(minikube docker-env)'       
                        sh 'docker pull oussamaslimani2001/flare-bank:testing'
                        sh 'kubectl apply -f app-deployment.yaml --validate=false'
                        sh 'kubectl apply -f app-service.yaml --validate=false'

                        timeout(time: 10, unit: 'MINUTES') {
                            waitUntil {
                                def podsReady = sh(script: 'kubectl get pods -l app=flare-bank -o jsonpath="{.items[*].status.containerStatuses[*].ready}"', returnStdout: true).trim()
                                return podsReady.contains('true')
                            }
                        }

                        sh 'kubectl get pods -o wide'
                        sh 'kubectl logs -l app=flare-bank'

                        def nodePortApp = sh(script: 'kubectl get svc flare-bank-service -o jsonpath="{.spec.ports[?(@.port==80)].nodePort}"', returnStdout: true).trim()
                        def nodePortMetrics = sh(script: 'kubectl get svc flare-bank-service -o jsonpath="{.spec.ports[?(@.port==9117)].nodePort}"', returnStdout: true).trim()
                        def minikubeIp = sh(script: 'minikube ip', returnStdout: true).trim()

                        def appUrl = "http://${minikubeIp}:${nodePortApp}"
                        def metricsUrl = "http://${minikubeIp}:${nodePortMetrics}/metrics"

                        echo "Application is accessible at: ${appUrl}"
                        echo "Metrics is accessible at: ${metricsUrl}"
                    } catch (err) {
                        echo "Error deploying to Minikube: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "Deployment to Minikube failed."
                    }
                }
            }
        }


        stage('Monitoring Setup') {
            steps {
                script {
                    echo 'Setting up Prometheus and Grafana'
                    try {
                        sh 'kubectl apply -f prometheus-deployment.yaml --validate=false'
                        sh 'kubectl apply -f prometheus-configmap.yaml --validate=false'
                        sh 'kubectl apply -f prometheus-service.yaml --validate=false'
                        sh 'kubectl apply -f grafana-deployment.yaml --validate=false'
                        sh 'kubectl apply -f grafana-service.yaml --validate=false'

                        timeout(time: 10, unit: 'MINUTES') {
                            waitUntil {
                                def prometheusReady = sh(script: 'kubectl get pods -l app=prometheus -o jsonpath="{.items[*].status.phase}"', returnStdout: true).trim()
                                def grafanaReady = sh(script: 'kubectl get pods -l app=grafana -o jsonpath="{.items[*].status.phase}"', returnStdout: true).trim()
                                return prometheusReady.contains('Running') && grafanaReady.contains('Running')
                            }
                        }

                        def prometheusNodePort = sh(script: 'kubectl get svc prometheus-service -o jsonpath="{.spec.ports[0].nodePort}"', returnStdout: true).trim()
                        def grafanaNodePort = sh(script: 'kubectl get svc grafana-service -o jsonpath="{.spec.ports[0].nodePort}"', returnStdout: true).trim()
                        def minikubeIp = sh(script: 'minikube ip', returnStdout: true).trim()

                        if (!prometheusNodePort || !grafanaNodePort) {
                            error "Failed to retrieve NodePorts for Prometheus and/or Grafana services."
                        }

                        def prometheusUrl = "http://${minikubeIp}:${prometheusNodePort}"
                        def grafanaUrl = "http://${minikubeIp}:${grafanaNodePort}"

                        echo "Prometheus is accessible at: ${prometheusUrl}"
                        echo "Grafana is accessible at: ${grafanaUrl}"

                    } catch (err) {
                        echo "Error setting up Prometheus and Grafana: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "Setup of Prometheus and Grafana failed."
                    }
                }
            }
        }


    }

    post {
        always {
            sh 'docker logout'
        }
    }
}
