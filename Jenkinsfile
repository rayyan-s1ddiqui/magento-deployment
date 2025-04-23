pipeline {
    agent any

    // 🔧 GLOBAL CONFIGURABLE VARIABLES
    environment {
        AWS_REGION       = 'us-east-1'                                           // AWS region
        GITHUB_REPO_URL  = 'https://github.com/rayyan-s1ddiqui/magento-deployment.git'  // GitHub repo URL
        ECR_REPO         = '975050208254.dkr.ecr.us-east-1.amazonaws.com/magento-repo'  // ECR repo URI (can be dynamic from TF)
        CODEBUILD_PROJ   = 'magento-codebuild'                                   // CodeBuild project name
        ARGOCD_APP       = 'my-app'                                              // ArgoCD app name
        ARGOCD_HOST      = 'argocd.yourdomain.com'                               // ArgoCD URL
        IMAGE_TAG        = ''                                                    // Will be dynamically set based on Git commit
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
                    // Use the Git commit hash as the image tag
                    IMAGE_TAG = sh(script: "git rev-parse --short HEAD", returnStdout: true).trim()
                    echo "✅ Set IMAGE_TAG to $IMAGE_TAG"
                }
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
                        
                        // Trigger AWS CodeBuild and pass the necessary environment variables
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
                        
                        // Log in to ArgoCD and trigger the sync
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
