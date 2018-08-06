pipeline {
    agent any
    stages {
        stage('Composer Install') {
            steps {
                sh 'make composer-install'
            }
        }
        stage('Lint Style') {
            steps {
                sh 'make lint-style'
            }
        }
        stage('Run Unit Tests') {
            steps {
                sh 'make test-unit'
            }
        }
    }
    post {
        always {
            deleteDir()
        }
    }
}