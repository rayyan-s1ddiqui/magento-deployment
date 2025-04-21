pipeline {
    agent any

    environment {
        AWS_REGION = 'us-east-1'                     // Change as needed
        ECR_REPO = '767397973879.dkr.ecr.us-east-1.amazonaws.com/magento-repo'  // Your ECR repo
        IMAGE_TAG = ''                              // Will be dynamically set
    }

    stages {

        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Set Image Tag') {
            steps {
                script {
                    // Use short git commit hash as image tag
                    IMAGE_TAG = sh(script: "git rev-parse --short HEAD", returnStdout: true).trim()
                }
            }
        }

        stage('Docker Build') {
            steps {
                script {
                    docker.build("${ECR_REPO}:${IMAGE_TAG}")
                }
            }
        }

        stage('Login to AWS ECR') {
            steps {
                withCredentials([[$class: 'AmazonWebServicesCredentialsBinding', 
                                  credentialsId: 'aws-jenkins-credentials']]) {  // Your Jenkins credential ID
                    script {
                        sh '''
                            aws ecr get-login-password --region $AWS_REGION | docker login --username AWS --password-stdin $ECR_REPO
                        '''
                    }
                }
            }
        }

        stage('Push to ECR') {
            steps {
                script {
                    sh """
                        docker push ${ECR_REPO}:${IMAGE_TAG}
                    """
                }
            }
        }

        stage('Trigger ArgoCD Sync') {
            steps {
                withCredentials([string(credentialsId: 'argocd-auth-token', variable: 'ARGOCD_AUTH_TOKEN')]) {
                    script {
                        sh '''
                            # Login to ArgoCD CLI
                            argocd login argocd.yourdomain.com --username admin --password $ARGOCD_AUTH_TOKEN --insecure

                            # Sync your application (replace 'my-app' with your actual ArgoCD app name)
                            argocd app sync my-app
                        '''
                    }
                }
            }
        }
    }

    post {
        success {
            echo "✅ Deployment Pipeline finished successfully! Image ${ECR_REPO}:${IMAGE_TAG} pushed and ArgoCD sync triggered."
        }
        failure {
            echo "❌ Pipeline failed. Check logs for details."
        }
    }
}
