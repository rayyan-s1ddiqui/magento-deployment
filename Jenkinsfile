pipeline {
    agent any

    environment {
        // 🌍 -------- Global Config --------
        GIT_REPO_URL     = 'https://github.com/rayyan-s1ddiqui/magento-deployment.git'
        DOCKER_IMAGE_NAME = 'vnvsa/magento'
        AWS_REGION        = 'us-east-1'
        ECR_REPO_NAME     = 'magento_repo'
        IMAGE_TAG         = 'latest'
        AWS_CREDENTIALS_ID = 'aws-creds'  // <-- ID from Jenkins Credentials Manager
    }

    stages {
        stage('📥 Clone Repository') {
            steps {
                git "${GIT_REPO_URL}"
            }
        }

        stage('🐳 Build Docker Image') {
            steps {
                script {
                    sh "docker build -t ${DOCKER_IMAGE_NAME}:${IMAGE_TAG} ."
                }
            }
        }

        stage('🔐 Login to ECR and Get Registry URI') {
            steps {
                withCredentials([[$class: 'AmazonWebServicesCredentialsBinding', credentialsId: "${AWS_CREDENTIALS_ID}"]]) {
                    script {
                        env.ECR_URL = sh(
                            script: "aws ecr describe-repositories --repository-names ${ECR_REPO_NAME} --region ${AWS_REGION} --query 'repositories[0].repositoryUri' --output text",
                            returnStdout: true
                        ).trim()

                        sh """
                        aws ecr get-login-password --region ${AWS_REGION} | \
                        docker login --username AWS --password-stdin ${env.ECR_URL}
                        """
                    }
                }
            }
        }

        stage('📤 Tag and Push Docker Image') {
            steps {
                script {
                    sh """
                    docker tag ${DOCKER_IMAGE_NAME}:${IMAGE_TAG} ${env.ECR_URL}:${IMAGE_TAG}
                    docker push ${env.ECR_URL}:${IMAGE_TAG}
                    """
                }
            }
        }
    }

    post {
        success {
            echo "✅ Image successfully pushed to ${env.ECR_URL}:${IMAGE_TAG}"
        }
        failure {
            echo '❌ Build failed. Check logs for details.'
        }
    }
}
