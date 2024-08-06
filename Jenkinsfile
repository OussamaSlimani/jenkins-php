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

        stage('Setup Helm') {
            steps {
                script {
                    echo 'Setting up Helm...'
                    try {
                        sh 'curl https://raw.githubusercontent.com/helm/helm/master/scripts/get-helm-3 | bash'
                        echo 'Helm installed successfully.'
                    } catch (err) {
                        echo "Error installing Helm: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "Helm installation failed."
                    }
                }
            }
        }

        stage('Install Prometheus and Grafana') {
            steps {
                script {
                    echo 'Installing Prometheus and Grafana using Helm...'
                    try {
                        sh 'helm repo add prometheus-community https://prometheus-community.github.io/helm-charts'
                        sh 'helm repo add grafana https://grafana.github.io/helm-charts'
                        sh 'helm repo update'
                        sh 'kubectl create namespace monitoring || true'
                        sh 'helm install prometheus prometheus-community/prometheus --namespace monitoring'
                        sh 'helm install grafana grafana/grafana --namespace monitoring'

                        timeout(time: 10, unit: 'MINUTES') {
                            waitUntil {
                                def prometheusReady = sh(script: 'kubectl get pods --namespace monitoring -l app=prometheus -o jsonpath="{.items[*].status.containerStatuses[*].ready}"', returnStdout: true).trim()
                                def grafanaReady = sh(script: 'kubectl get pods --namespace monitoring -l app=grafana -o jsonpath="{.items[*].status.containerStatuses[*].ready}"', returnStdout: true).trim()
                                return prometheusReady.contains('true') && grafanaReady.contains('true')
                            }
                        }
                        echo 'Prometheus and Grafana installed and ready.'
                    } catch (err) {
                        echo "Error installing Prometheus and Grafana: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "Prometheus and Grafana installation failed."
                    }
                }
            }
        }

        stage('Access Prometheus and Grafana') {
            steps {
                script {
                    echo 'Setting up port forwarding for Prometheus and Grafana...'
                    try {
                        sh 'kubectl --namespace monitoring port-forward svc/prometheus-server 9090:80 &> /dev/null &'
                        sh 'kubectl --namespace monitoring port-forward svc/grafana 3000:80 &> /dev/null &'
                        echo 'Port forwarding setup. Access Prometheus at http://localhost:9090 and Grafana at http://localhost:3000'
                    } catch (err) {
                        echo "Error setting up port forwarding: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "Port forwarding setup failed."
                    }
                }
            }
        }

        stage('Get Grafana Admin Password') {
            steps {
                script {
                    echo 'Retrieving Grafana admin password...'
                    try {
                        def password = sh(script: 'kubectl get secret --namespace monitoring grafana -o jsonpath="{.data.admin-password}" | base64 --decode', returnStdout: true).trim()
                        echo "Grafana admin password: ${password}"
                    } catch (err) {
                        echo "Error retrieving Grafana admin password: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "Retrieving Grafana admin password failed."
                    }
                }
            }
        }

        stage('Configure Grafana Data Source') {
            steps {
                script {
                    echo 'Configuring Grafana data source for Prometheus...'
                    try {
                        // You can use Grafana's HTTP API to configure the data source.
                        // Here, using `curl` to post configuration. Adjust as necessary.

                        sh '''
                        curl -X POST -H "Content-Type: application/json" \
                        -d '{"name":"Prometheus","type":"prometheus","url":"http://prometheus-server.monitoring.svc.cluster.local:80","access":"proxy","isDefault":true}' \
                        http://admin:$(kubectl get secret --namespace monitoring grafana -o jsonpath="{.data.admin-password}" | base64 --decode)@localhost:3000/api/datasources
                        '''
                        echo 'Grafana data source configured.'
                    } catch (err) {
                        echo "Error configuring Grafana data source: ${err}"
                        currentBuild.result = 'FAILURE'
                        error "Grafana data source configuration failed."
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
