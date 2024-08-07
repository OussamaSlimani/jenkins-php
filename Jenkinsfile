pipeline {
    agent any

    environment {
        DOCKERHUB_CREDENTIALS = credentials('Dockerhub')
        DOCKER_CONTENT_TRUST = '0'
        MINIKUBE_IP = ''
        NODE_PORT_APP = ''
        NODE_PORT_METRICS = ''
        NODE_PORT_PROMETHEUS = '30090'
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

                        env.NODE_PORT_APP = sh(script: 'kubectl get svc flare-bank-service -o jsonpath="{.spec.ports[?(@.port==80)].nodePort}"', returnStdout: true).trim()
                        env.NODE_PORT_METRICS = sh(script: 'kubectl get svc flare-bank-service -o jsonpath="{.spec.ports[?(@.port==9117)].nodePort}"', returnStdout: true).trim()
                        env.MINIKUBE_IP = sh(script: 'minikube ip', returnStdout: true).trim()

                        def appUrl = "http://${env.MINIKUBE_IP}:${env.NODE_PORT_APP}"
                        def metricsUrl = "http://${env.MINIKUBE_IP}:${env.NODE_PORT_METRICS}/metrics"

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

        stage('Prometheus Setup') {
            steps {
                script {
                    echo 'Start setting up Prometheus'
                    try {
                        // Apply Prometheus deployment and service configuration
                        sh 'kubectl apply -f prometheus-deployment.yaml --validate=false'
                        sh 'kubectl apply -f prometheus-service.yaml --validate=false'

                        timeout(time: 5, unit: 'MINUTES') {
                            waitUntil {
                                def podsReady = sh(script: 'kubectl get pods -l app=prometheus -o jsonpath="{.items[*].status.containerStatuses[*].ready}"', returnStdout: true).trim()
                                return podsReady.contains('true')
                            }
                        }

                        sh 'kubectl get pods -o wide'
                        sh 'kubectl logs -l app=prometheus'

                        def prometheusUrl = "http://${env.MINIKUBE_IP}:${env.NODE_PORT_PROMETHEUS}"

                        echo "Prometheus is accessible at: ${prometheusUrl}"
                        echo "Ensure that Prometheus is configured to scrape metrics from http://${env.MINIKUBE_IP}:${env.NODE_PORT_METRICS}/metrics"
                    } catch (err) {
                        echo "Error setting up Prometheus: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "Prometheus setup failed."
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
