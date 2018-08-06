pipeline {
    agent any
    stages {
        stage('Lint Style') {
            steps {
                sh 'make lint-style'
            }
        }
        stage('Composer Install') {
            steps {
                sh 'make composer-install'
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
            deleteDir() /* clean up our workspace */
        }
    }
}