pipeline {
    agent any

    environment {
        DOCKERHUB_CREDENTIALS = credentials('Dockerhub')
        DOCKER_CONTENT_TRUST = '0'
    }

    stages {
        
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

        stage('Setup Monitoring') {
            steps {
                script {
                    echo 'Setting up monitoring with Prometheus and Grafana...'
                    try {
                        sh 'kubectl apply -f prometheus-config.yaml --validate=false'
                        sh 'kubectl apply -f prometheus-deployment.yaml --validate=false'
                        sh 'kubectl apply -f prometheus-service.yaml --validate=false'
                        sh 'kubectl apply -f grafana-deployment.yaml --validate=false'
                        sh 'kubectl apply -f grafana-service.yaml --validate=false'

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

    }
}