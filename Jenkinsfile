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

        stage('Setup Monitoring') {
            steps {
                script {
                    echo 'Setting up monitoring with Prometheus and Grafana...'
                    try {
                        // Ensure Minikube is running
                        sh 'minikube start'

                        // Install Helm
                        sh 'curl https://raw.githubusercontent.com/helm/helm/master/scripts/get-helm-3 | bash'

                        // Add Prometheus and Grafana Helm repositories
                        sh 'helm repo add prometheus-community https://prometheus-community.github.io/helm-charts'
                        sh 'helm repo add grafana https://grafana.github.io/helm-charts'
                        sh 'helm repo update'

                        // Create namespace for monitoring
                        sh 'kubectl create namespace monitoring'

                        // Install Prometheus
                        sh 'helm install prometheus prometheus-community/prometheus --namespace monitoring'

                        // Install Grafana
                        sh 'helm install grafana grafana/grafana --namespace monitoring'

                        // Wait until Prometheus and Grafana pods are running
                        timeout(time: 10, unit: 'MINUTES') {
                            waitUntil {
                                def prometheusReady = sh(script: 'kubectl get pods --namespace monitoring -l app=prometheus -o jsonpath="{.items[*].status.containerStatuses[*].ready}"', returnStdout: true).trim()
                                def grafanaReady = sh(script: 'kubectl get pods --namespace monitoring -l app=grafana -o jsonpath="{.items[*].status.containerStatuses[*].ready}"', returnStdout: true).trim()
                                return prometheusReady.contains('true') && grafanaReady.contains('true')
                            }
                        }
                        echo 'Prometheus and Grafana are ready.'

                        // Access Prometheus and Grafana
                        sh 'kubectl --namespace monitoring port-forward svc/prometheus-server 9090:80 &> /dev/null &'
                        sh 'kubectl --namespace monitoring port-forward svc/grafana 3000:80 &> /dev/null &'

                        // Get Grafana admin password
                        def grafanaPassword = sh(script: 'kubectl get secret --namespace monitoring grafana -o jsonpath="{.data.admin-password}" | base64 --decode ; echo', returnStdout: true).trim()
                        echo "Grafana Admin Password: ${grafanaPassword}"

                        echo "Prometheus is accessible at: http://localhost:9090"
                        echo "Grafana is accessible at: http://localhost:3000"

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
