pipeline {
    parameters {
        string(name: 'KUBE_DEV_NAMESPACE',         description: 'Kubernetes Development Namespace',                  defaultValue: 'intake')
    	string(name: 'DOCKER_DEV_REGISTRY_URL',    description: 'docker registry',                                   defaultValue: 'docker-registry-default.apps.playcourt.id')
    	string(name: 'SKIP_TLS',                   description: 'Skip TLS',                                          defaultValue: 'true')
    	string(name: 'DOCKER_IMAGE_NAME',          description: 'Docker Image Name',                                 defaultValue: 'intake-full')
    }
    agent none
    options {
        skipDefaultCheckout()
    }
    stages {
        stage('Checkout SCM') {
            agent { label "jenkins-agent-nodejs-1" }
            steps {
                checkout scm
                script {
                    echo "get COMMIT_ID"
                    sh 'echo -n $(git rev-parse --short HEAD) > ./commit-id'
                    commitId = readFile('./commit-id')
                }
                stash(name: 'ws', includes:'**,./commit-id')
            }
        }

        stage('Initialize') {
            parallel {
                stage("Agent: Nodejs") {
                    agent { label "jenkins-agent-nodejs-1" }
                    steps {
                        cleanWs()
                    }
                }
                stage("Agent: Docker") {
                    agent { label "jenkins-agent-docker-1" }
                    steps {
                        cleanWs()
                    }
                }
            }
        }

        stage('Unit Test') {
            agent { label "jenkins-agent-nodejs-1" }
            steps {
                unstash 'ws'
                echo "Do Unit Test Here"
            }    
        }

        stage('SonarQube Scan') {
            environment{
                sonar_auth = credentials('sonar_auth')
            }
            when {
                anyOf {
                    branch 'master'
                    branch 'release-*'
                }
            }
            agent { label "jenkins-agent-nodejs-1" }
            steps {
                unstash 'ws'
                echo "Run SonarQube"
                script {
                    echo "defining sonar-scanner"
                    def scannerHome = tool 'SonarQube Scanner' ;
                    withSonarQubeEnv('SonarQube') {
                        sh "${scannerHome}/bin/sonar-scanner"
                    }
                    sh "curl -s -u ${env.sonar_auth}: https://sonarqube.playcourt.id/api/qualitygates/project_status?projectKey=intake | python -c \"import sys, json ; print(json.load(sys.stdin)['projectStatus']['status'])\" > sonar.result"
                    sonar_result = readFile 'sonar.result'
                    echo "${sonar_result}"
                }   
            }
        }
        stage('Build') {
            when {
                anyOf {
                    branch 'master'
                    branch 'release-*'
                }
            }
            agent { label "jenkins-agent-docker-1" }
            steps {
                unstash 'ws'
                script {
                    echo "get COMMIT_ID"
                    commitId = readFile('./commit-id')
                }
                sh 'rm -rf ./commit-id'
                sh "docker build -t '${params.KUBE_DEV_NAMESPACE}/${params.DOCKER_IMAGE_NAME}:${BUILD_NUMBER}-${commitId}' ."
            }
        }

        stage('Deploy to DEV') {
            environment {
                KUBE_DEV_TOKEN = credentials('OC_REGISTRY_TOKEN')
            }
            when {
                branch 'master'
            }
            agent { label "jenkins-agent-docker-1" }
            steps {
                unstash 'ws'
                script {
                    echo "get COMMIT_ID"
                    commitId = readFile('./commit-id')
                }
                sh 'rm -rf ./commit-id'
                sh "docker tag '${params.KUBE_DEV_NAMESPACE}/${params.DOCKER_IMAGE_NAME}:${BUILD_NUMBER}-${commitId}' '${params.DOCKER_DEV_REGISTRY_URL}/${params.KUBE_DEV_NAMESPACE}/${params.DOCKER_IMAGE_NAME}:${BUILD_NUMBER}-${commitId}' "
                sh "docker tag '${params.KUBE_DEV_NAMESPACE}/${params.DOCKER_IMAGE_NAME}:${BUILD_NUMBER}-${commitId}' '${params.DOCKER_DEV_REGISTRY_URL}/${params.KUBE_DEV_NAMESPACE}/${params.DOCKER_IMAGE_NAME}:latest' "
                sh "docker login ${params.DOCKER_DEV_REGISTRY_URL} -u jenkins -p ${env.KUBE_DEV_TOKEN}"
                sh "docker push ${params.DOCKER_DEV_REGISTRY_URL}/${params.KUBE_DEV_NAMESPACE}/${params.DOCKER_IMAGE_NAME}:latest"
                sh "docker push ${params.DOCKER_DEV_REGISTRY_URL}/${params.KUBE_DEV_NAMESPACE}/${params.DOCKER_IMAGE_NAME}:${BUILD_NUMBER}-${commitId}"
                sh "docker rmi -f ${params.KUBE_DEV_NAMESPACE}/${params.DOCKER_IMAGE_NAME}:${BUILD_NUMBER}-${commitId}"
                sh "docker rmi -f ${params.DOCKER_DEV_REGISTRY_URL}/${params.KUBE_DEV_NAMESPACE}/${params.DOCKER_IMAGE_NAME}:${BUILD_NUMBER}-${commitId}"
                sh "docker rmi -f ${params.DOCKER_DEV_REGISTRY_URL}/${params.KUBE_DEV_NAMESPACE}/${params.DOCKER_IMAGE_NAME}:latest"
            }
        }

        stage('Performance Test') {
            environment {
                KUBE_DEV_TOKEN = credentials('OC_REGISTRY_TOKEN')
            }
            when {
                anyOf {
                    branch 'master'
                    branch 'release-*'
                }
            }
            agent { label "jenkins-agent-docker-1" }
            steps {
                echo "Do Performance Test - "
            }
        }
        
        stage('Deploy to PRODUCTION') {
            agent none
            when {
                branch 'release-*'
            }
            steps {
                echo "Deploy to PRODUCTION"
                script {
                    if (sonar_result == 'ERROR' ) {
                        echo 'sonarqube analysis is not pass'
                    } else {
                        timeout(15) {
                            input message: 'Deploy to PRODUCTION?', ok: 'Deploy'
                        }
                    }
                }              
            }
        }
    }
    post {
        always {
            echo "Notify Build"
            //Call slack
        }
    }

}
