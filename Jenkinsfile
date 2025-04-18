pipeline {
    agent any
    environment {
        AWS_REGION = "${AWS_REGION ?: 'us-east-1'}"
        REPO_NAME = "magento-repo"
        GIT_REPO = "${GIT_REPO ?: 'https://github.com/rayyan-s1ddiqui/magento-deployment.git'}"
        ARGOCD_SERVER = "https://kubernetes.default.svc"
        K8S_NAMESPACE = "default"
    }
    stages {
        stage('Prepare') {
            steps {
                withCredentials([[$class: 'AmazonWebServicesCredentialsBinding', credentialsId: 'aws-credentials-id']]) {
                    script {
                        def accountId = sh(returnStdout: true, script: "aws sts get-caller-identity --query Account --output text").trim()
                        env.ECR_REGISTRY = "${accountId}.dkr.ecr.${env.AWS_REGION}.amazonaws.com"
                        env.IMAGE_NAME = "${env.ECR_REGISTRY}/${env.REPO_NAME}"
                    }
                    sh 'echo "Using AWS Region: ${AWS_REGION}"'
                    sh 'echo "ECR Registry: ${ECR_REGISTRY}"'
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
            sh 'docker logout ${ECR_REGISTRY}'
        }
    }
}
