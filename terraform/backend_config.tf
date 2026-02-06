# Backend configuration for S3 state storage
#
# IMPORTANT: This backend configuration is initially commented out.
# The workflow will:
# 1. First run terraform apply to create the S3 bucket and DynamoDB table
# 2. Then uncomment this block and run terraform init -migrate-state
# 3. State will be migrated to S3 for all future runs

# terraform {
#   backend "s3" {
#     bucket         = "jaws-terraform-state-ACCOUNT_ID"  # Will be updated by workflow
#     key            = "production/terraform.tfstate"
#     region         = "ca-central-1"
#     encrypt        = true
#     dynamodb_table = "jaws-terraform-locks"
#   }
# }
