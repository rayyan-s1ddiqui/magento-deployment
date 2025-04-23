pipeline {
    agent any

    environment {
        AWS_REGION       = 'us-east-1'
        GITHUB_REPO_URL  = 'https://github.com/rayyan-s1ddiqui/magento-deployment.git'
        ECR_REPO         = '975050208254.dkr.ecr.us-east-1.amazonaws.com/magento-repo'
        CODEBUILD_PROJ   = 'magento-codebuild'
        ARGOCD_APP       = 'my-app'
        ARGOCD_HOST      = 'argocd.yourdomain.com'
    }

    stages {

        stage('Checkout') {
            steps {
                echo "🔄 Checking out Git repository: ${GITHUB_REPO_URL}"
                checkout scm
            }
        }

        stage('Set Image Tag') {
            steps {
                script {
                    env.IMAGE_TAG = sh(script: "git rev-parse --short HEAD", returnStdout: true).trim()
                    echo "✅ Set IMAGE_TAG to ${env.IMAGE_TAG}"
                }
            }
        }

        stage('Trigger AWS CodeBuild') {
            steps {
                withAWS(credentials: 'aws-jenkins-credentials', region: "${AWS_REGION}") {
                    script {
                        echo "🚀 Triggering AWS CodeBuild with:"
                        echo "   REPO_URL: ${GITHUB_REPO_URL}"
                        echo "   IMAGE_TAG: ${env.IMAGE_TAG}"
                        echo "   ECR_REPO: ${ECR_REPO}"

                        def build = awsCodeBuild(
                            projectName: "${CODEBUILD_PROJ}",
                            environmentVariablesOverride: [
                                [name: 'REPO_URL', value: "${GITHUB_REPO_URL}", type: 'PLAINTEXT'],
                                [name: 'IMAGE_TAG', value: "${env.IMAGE_TAG}", type: 'PLAINTEXT'],
                                [name: 'ECR_REPO', value: "${ECR_REPO}", type: 'PLAINTEXT']
                            ]
                        )
                        echo "✅ CodeBuild started with status: ${build.buildStatus}"
                    }
                }
            }
        }

        stage('Trigger ArgoCD Sync') {
            steps {
                withCredentials([string(credentialsId: 'argocd-auth-token', variable: 'ARGOCD_AUTH_TOKEN')]) {
                    script {
                        echo "🔄 Triggering ArgoCD sync for the app: ${ARGOCD_APP}"
                        sh """
                        curl -k -X POST https://${ARGOCD_HOST}/api/v1/applications/${ARGOCD_APP}/sync \
                          -H "Authorization: Bearer ${ARGOCD_AUTH_TOKEN}" \
                          -H "Content-Type: application/json"
                        """
                        echo "✅ ArgoCD sync requested for ${ARGOCD_APP}"
                    }
                }
            }
        }
    }

    post {
        success {
            echo "✅ Pipeline completed successfully for IMAGE_TAG ${env.IMAGE_TAG}. ArgoCD sync triggered for ${ARGOCD_APP}."
        }
        failure {
            echo "❌ Pipeline failed. Check logs for more info."
        }
    }
}
