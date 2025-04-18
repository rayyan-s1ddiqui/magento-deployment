pipeline {
    agent any
    environment {
        AWS_REGION = "${AWS_REGION ?: 'us-east-1'}"
        ECR_REGISTRY = "${sh(returnStdout: true, script: 'aws sts get-caller-identity --query Account --output text').trim() + '.dkr.ecr.' + env.AWS_REGION + '.amazonaws.com'}"
        REPO_NAME = "magento"
        IMAGE_NAME = "${ECR_REGISTRY}/${REPO_NAME}"
        GIT_REPO = "${GIT_REPO ?: 'https://github.com/rayyan-s1ddiqui/magento-deployment'"
        ARGOCD_SERVER = "https://kubernetes.default.svc"
        K8S_NAMESPACE = "default"
    }
    stages {
        stage('Prepare') {
            steps {
                withCredentials([usernamePassword(credentialsId: '697d826f-cf37-4bc6-b717-5c666c65b9a5', usernameVariable: 'AWS_ACCESS_KEY_ID', passwordVariable: 'AWS_SECRET_ACCESS_KEY')]) { 
                    sh 'echo "Configuring AWS credentials"'
                    sh 'mkdir -p ~/.aws'
                    sh 'echo "[default]\\naws_access_key_id=${AWS_ACCESS_KEY_ID}\\naws_secret_access_key=${AWS_SECRET_ACCESS_KEY}" > ~/.aws/credentials'
                    sh 'aws configure set region ${AWS_REGION}'
                    sh 'aws ecr get-login-password --region ${AWS_REGION} | docker login --username AWS --password-stdin ${ECR_REGISTRY}'
                }
            }
        }
        stage('Build') {
            steps {
                sh 'docker build -t ${IMAGE_NAME}:latest .'
                sh 'docker tag ${IMAGE_NAME}:latest ${IMAGE_NAME}:${BUILD_NUMBER}'
                sh 'docker push ${IMAGE_NAME}:latest'
                sh 'docker push ${IMAGE_NAME}:${BUILD_NUMBER}'
            }
        }
        stage('Deploy') {
            steps {
                sh 'kubectl apply -f k8s/'
                sh 'argocd app sync magento-app --server ${ARGOCD_SERVER} --namespace ${K8S_NAMESPACE} --grpc-web'
            }
        }
    }
    post {
        always {
            sh 'rm -rf ~/.aws/credentials'
            sh 'docker logout ${ECR_REGISTRY}'
        }
    }
}
