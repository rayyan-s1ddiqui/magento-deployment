pipeline {
    agent any
    environment {
        AWS_REGION = "${AWS_REGION ?: 'us-east-1'}"
        ECR_REGISTRY = "${ECR_REGISTRY ?: sh(returnStdout: true, script: 'aws sts get-caller-identity --query Account --output text').trim() + '.dkr.ecr.' + env.AWS_REGION + '.amazonaws.com'}"
        REPO_NAME = "magento"
        IMAGE_NAME = "${ECR_REGISTRY}/${REPO_NAME}"
        GIT_REPO = "${GIT_REPO ?: 'https://github.com/yourusername/magento-deployment.git'}"
        ARGOCD_SERVER = "http://ad434cd11f2ea46a1951f428cdd61c69-1036000244.us-east-1.elb.amazonaws.com:8080"
        K8S_NAMESPACE = "default"
        AWS_CREDENTIALS = credentials('aws-credentials-id')
    }
    stages {
        stage('Prepare') {
            steps {
                sh 'echo "Using AWS Region: ${AWS_REGION}"'
                sh 'echo "ECR Registry: ${ECR_REGISTRY}"'
                sh 'aws configure set region ${AWS_REGION}'
                sh 'aws ecr get-login-password --region ${AWS_REGION} | docker login --username AWS --password-stdin ${ECR_REGISTRY}'
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
        stage('Deploy with ArgoCD') {
            steps {
                // Apply your Kubernetes manifests first
                sh 'kubectl apply -f k8s/'

                // Trigger ArgoCD app sync
                sh """
                argocd app sync magento-app --server ${ARGOCD_SERVER} --namespace ${K8S_NAMESPACE} --grpc-web
                """
            }
        }
    }
    post {
        always {
            sh 'docker logout ${ECR_REGISTRY}'
        }
    }
}
