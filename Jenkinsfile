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

        stage('Trigger AWS CodeBuild') {
            steps {
                withCredentials([[$class: 'AmazonWebServicesCredentialsBinding', credentialsId: 'aws-jenkins-credentials']]) {
                    script {
                        echo "🚀 Triggering AWS CodeBuild with the following parameters:"
                        echo "   REPO_URL: $GITHUB_REPO_URL"
                        echo "   IMAGE_TAG: $IMAGE_TAG"
                        echo "   ECR_REPO: $ECR_REPO"

                        def build = codeBuild(
                            projectName: "${CODEBUILD_PROJ}",
                            credentialsType: 'keys',
                            accessKey: "${AWS_ACCESS_KEY_ID}",
                            secretKey: "${AWS_SECRET_ACCESS_KEY}",
                            region: "${AWS_REGION}",
                            environmentVariables: [
                                [name: 'REPO_URL', value: "${GITHUB_REPO_URL}"],
                                [name: 'IMAGE_TAG', value: "${IMAGE_TAG}"],
                                [name: 'ECR_REPO', value: "${ECR_REPO}"]
                            ]
                        )
                        echo "✅ CodeBuild started: ${build.buildId}"
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
                        curl -k -X POST https://${ARGOCD_HOST}/api/v1/applications/${ARGOCD_APP}/sync \
                          -H "Authorization: Bearer $ARGOCD_AUTH_TOKEN" \
                          -H "Content-Type: application/json"
                        """
                        echo "✅ ArgoCD sync requested for $ARGOCD_APP"
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
