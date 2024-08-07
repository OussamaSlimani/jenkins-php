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
                        // Deploy Prometheus
                        sh 'kubectl apply -f prometheus-deployment.yaml --validate=false'
                        sh 'kubectl apply -f prometheus-configmap.yaml --validate=false'
                        sh 'kubectl apply -f prometheus-service.yaml --validate=false'

                        // Deploy Grafana
                        sh 'kubectl apply -f grafana-deployment.yaml --validate=false'
                        sh 'kubectl apply -f grafana-service.yaml --validate=false'

                        // Wait for Prometheus and Grafana to be ready
                        timeout(time: 10, unit: 'MINUTES') {
                            waitUntil {
                                def prometheusReady = sh(script: 'kubectl get pods -l app=prometheus -o jsonpath="{.items[*].status.phase}"', returnStdout: true).trim()
                                def grafanaReady = sh(script: 'kubectl get pods -l app=grafana -o jsonpath="{.items[*].status.phase}"', returnStdout: true).trim()
                                return prometheusReady.contains('Running') && grafanaReady.contains('Running')
                            }
                        }

                        // Get URLs for Prometheus and Grafana
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


   


/*
        stage('Setup Monitoring') {
            steps {
                script {
                    echo 'Setting up monitoring with Prometheus and Grafana...'
                    try {
                        sh 'docker pull prom/prometheus:main'
                        sh 'kubectl apply -f prometheus-config.yaml --validate=false'
                        sh 'kubectl apply -f prometheus-deployment.yaml --validate=false'
                        sh 'kubectl apply -f prometheus-service.yaml --validate=false'

                        sh 'docker pull grafana/grafana:main'
                        sh 'kubectl apply -f grafana-deployment.yaml --validate=false'
                        sh 'kubectl apply -f grafana-service.yaml --validate=false'

                        timeout(time: 10, unit: 'MINUTES') {
                            waitUntil {
                                def prometheusReady = sh(script: 'kubectl get pods -l app=prometheus -o jsonpath="{.items[*].status.containerStatuses[*].ready}"', returnStdout: true).trim()
                                return prometheusReady.contains('true')
                            }
                        }
                        echo 'Prometheus is ready.'

                        timeout(time: 10, unit: 'MINUTES') {
                            waitUntil {
                                def grafanaReady = sh(script: 'kubectl get pods -l app=grafana -o jsonpath="{.items[*].status.containerStatuses[*].ready}"', returnStdout: true).trim()
                                return grafanaReady.contains('true')
                            }
                        }
                        echo 'Grafana is ready.'

                        def prometheusUrl = sh(script: 'minikube service prometheus-service --url', returnStdout: true).trim()
                        def grafanaUrl = sh(script: 'minikube service grafana-service --url', returnStdout: true).trim()
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
        */

    }

    post {
        always {
            sh 'docker logout'
        }
    }
}
