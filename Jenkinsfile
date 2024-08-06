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

        stage('Deploy Monitoring Tools') {
            steps {
                script {
                    echo 'Deploying Prometheus and Grafana...'
                    try {
                        sh 'kubectl apply -f prometheus.yaml'
                        sh 'kubectl apply -f grafana.yaml'
                    } catch (err) {
                        echo "Error deploying monitoring tools: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "Failed to deploy Prometheus and Grafana."
                    }
                }
            }
        }

        stage('Access Monitoring Tools') {
            steps {
                script {
                    echo 'Setting up port forwarding for Prometheus and Grafana...'
                    try {
                        // Run port-forwarding in the background
                        sh 'kubectl port-forward svc/prometheus -n monitoring 9090:9090 &'
                        sh 'kubectl port-forward svc/grafana -n monitoring 3000:3000 &'

                        // Allow some time for port-forwarding to start
                        sleep time: 60, unit: 'SECONDS'

                        // Fetch the URLs for Prometheus and Grafana
                        def prometheusUrl = 'http://localhost:9090'
                        def grafanaUrl = 'http://localhost:3000'

                        echo "Prometheus is accessible at: ${prometheusUrl}"
                        echo "Grafana is accessible at: ${grafanaUrl}"
                    } catch (err) {
                        echo "Error setting up port forwarding: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "Failed to set up port forwarding for Prometheus and Grafana."
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
