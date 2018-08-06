pipeline {
    agent any
    stages {
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