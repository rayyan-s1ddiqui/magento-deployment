pipeline {
    agent any

    environment {
        // 🌍 -------- Global Config --------
        GIT_REPO_URL     = 'https://github.com/rayyan-s1ddiqui/magento-deployment.git'
        DOCKER_IMAGE_NAME = 'phoenixmedia/magento'
        AWS_REGION        = 'us-east-1'
        ECR_REPO_NAME     = 'magento-repo'
        IMAGE_TAG         = 'latest'
        AWS_CREDENTIALS_ID = 'aws-creds'  // <-- ID from Jenkins Credentials Manager
        GIT_CRED_ID       = '4906df33-3a14-4a82-a76c-37dd2033decf'
    }

    stages {
        stage('📥 Clone Repository') {
            steps {
                git credentialsId: "${GIT_CRED_ID}",
                    url: "${GIT_REPO_URL}",
                    branch: 'main'
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

        stage('✏️ Update Deployment Manifest with Image URI') {
            steps {
                withCredentials([usernamePassword(credentialsId: "${GIT_CRED_ID}", usernameVariable: 'GIT_USER', passwordVariable: 'GIT_TOKEN')]) {
                    script {
                        def manifestPath = 'k8s/deployment.yaml'
                        def repoWithCreds = "https://${GIT_USER}:${GIT_TOKEN}@github.com/rayyan-s1ddiqui/magento-deployment.git"

                        sh """
                        sed -i 's|image:.*|image: ${env.ECR_URL}:${IMAGE_TAG}|' ${manifestPath}
                        git config --global user.email "jenkins@local"
                        git config --global user.name "jenkins"
                        git remote set-url origin ${repoWithCreds}
                        git add ${manifestPath}
                        git commit -m "🔄 Auto-update image to ${env.ECR_URL}:${IMAGE_TAG}"
                        git push origin HEAD:main
                        """
                    }
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
