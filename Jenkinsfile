pipeline {
    agent any

    environment {
        DOCKERHUB_CREDENTIALS = credentials('Dockerhub')
        DOCKER_CONTENT_TRUST = '0'
    }

    stages {
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

                        // Update Helm chart repository
                        sh 'helm repo add my-charts https://example.com/charts'
                        sh 'helm repo update'

                        // Install or upgrade Helm chart
                        sh 'helm upgrade --install flare-bank my-charts/flare-bank --set image.tag=testing'

                        timeout(time: 10, unit: 'MINUTES') {
                            waitUntil {
                                def podsReady = sh(script: 'kubectl get pods -l app=flare-bank -o jsonpath="{.items[*].status.containerStatuses[*].ready}"', returnStdout: true).trim()
                                return podsReady.contains('true')
                            }
                        }

                        sh 'kubectl get pods -o wide'
                        sh 'kubectl logs -l app=flare-bank'

                        def url = sh(script: 'minikube service flare-bank-service --url', returnStdout: true).trim()
                        echo "Application is accessible at: ${url} || true"
                    } catch (err) {
                        echo "Error deploying to Minikube: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "Deployment to Minikube failed."
                    }
                }
            }
        }

        stage('Setup Monitoring') {
            steps {
                script {
                    echo 'Setting up monitoring with Prometheus and Grafana...'
                    try {
                        // Update Helm chart repository for monitoring tools
                        sh 'helm repo add monitoring https://prometheus-community.github.io/helm-charts'
                        sh 'helm repo update'

                        // Install Prometheus and Grafana using Helm
                        sh 'helm upgrade --install prometheus monitoring/prometheus'
                        sh 'helm upgrade --install grafana monitoring/grafana'

                        timeout(time: 5, unit: 'MINUTES') {
                            waitUntil {
                                def prometheusReady = sh(script: 'kubectl get pods -l app=prometheus -o jsonpath="{.items[*].status.containerStatuses[*].ready}"', returnStdout: true).trim()
                                return prometheusReady.contains('true')
                            }
                        }
                        echo 'Prometheus is ready.'

                        timeout(time: 5, unit: 'MINUTES') {
                            waitUntil {
                                def grafanaReady = sh(script: 'kubectl get pods -l app=grafana -o jsonpath="{.items[*].status.containerStatuses[*].ready}"', returnStdout: true).trim()
                                return grafanaReady.contains('true')
                            }
                        }
                        echo 'Grafana is ready.'

                        def prometheusUrl = sh(script: 'minikube service prometheus-server --url', returnStdout: true).trim()
                        def grafanaUrl = sh(script: 'minikube service grafana --url', returnStdout: true).trim()
                        echo "Prometheus is accessible at: ${prometheusUrl}"
                        echo "Grafana is accessible at: ${grafanaUrl}"
                    } catch (err) {
                        echo "Error setting up monitoring: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "Monitoring setup failed."
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
