pipeline {
    agent any

    environment {
        AWS_REGION       = 'us-east-1'
        GITHUB_REPO_URL  = 'https://github.com/rayyan-s1ddiqui/magento-deployment.git'
        ECR_REPO         = '975050208254.dkr.ecr.us-east-1.amazonaws.com/magento-repo'
        CODEBUILD_PROJ   = 'magento-codebuild'
        ARGOCD_APP       = 'my-app'
        ARGOCD_HOST      = 'argocd.yourdomain.com'
        IMAGE_TAG        = ''
    }

    stages {

        stage('Checkout') {
            steps {
                echo "🔄 Checking out Git repository: $GITHUB_REPO_URL"
                checkout scm
            }
        }

        stage('Set Image Tag') {
            steps {
                script {
                    IMAGE_TAG = sh(script: "git rev-parse --short HEAD", returnStdout: true).trim()
                    echo "✅ Set IMAGE_TAG to $IMAGE_TAG"
                }
            }
        }

        stage('Install AWS CLI') {
            steps {
                echo "🔧 Installing AWS CLI..."
                sh '''
                if ! command -v aws &> /dev/null
                then
                    echo "AWS CLI not found. Installing..."
                    curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
                    unzip awscliv2.zip
                    ./aws/install
                    echo "AWS CLI installed successfully."
                else
                    echo "AWS CLI is already installed."
                fi
                '''
            }
        }

        stage('Trigger AWS CodeBuild') {
            steps {
                withCredentials([[$class: 'AmazonWebServicesCredentialsBinding', credentialsId: 'aws-jenkins-credentials']]) {
                    script {
                        echo "🚀 Triggering AWS CodeBuild with the following parameters:"
                        echo "   REPO_URL: $GITHUB_REPO_URL"
                        echo "   IMAGE_TAG: $IMAGE_TAG"
                        echo "   ECR_REPO: $ECR_REPO"
                        sh """
                        aws codebuild start-build \
                          --region $AWS_REGION \
                          --project-name $CODEBUILD_PROJ \
                          --environment-variables-override \
                              name=REPO_URL,value=$GITHUB_REPO_URL,type=PLAINTEXT \
                              name=IMAGE_TAG,value=$IMAGE_TAG,type=PLAINTEXT \
                              name=ECR_REPO,value=$ECR_REPO,type=PLAINTEXT
                        """
                    }
                }
            }
        }

        stage('Trigger ArgoCD Sync') {
            steps {
                withCredentials([string(credentialsId: 'argocd-auth-token', variable: 'ARGOCD_AUTH_TOKEN')]) {
                    script {
                        echo "🔄 Triggering ArgoCD sync for the app: $ARGOCD_APP"
                        sh """
                        argocd login $ARGOCD_HOST --username admin --password $ARGOCD_AUTH_TOKEN --insecure
                        argocd app sync $ARGOCD_APP
                        """
                    }
                }
            }
        }
    }

    post {
        success {
            echo "✅ CodeBuild started for IMAGE_TAG $IMAGE_TAG and ArgoCD sync triggered successfully for $ARGOCD_APP."
        }
        failure {
            echo "❌ Pipeline failed. Check logs for details."
        }
    }
}
