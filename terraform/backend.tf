# S3 bucket for Terraform state storage
resource "aws_s3_bucket" "terraform_state" {
  bucket = "jaws-terraform-state-${data.aws_caller_identity.current.account_id}"

  # Prevent accidental deletion of state bucket
  lifecycle {
    prevent_destroy = false  # Set to true after first successful deployment
  }

  tags = {
    Name        = "JAWS Terraform State"
    Application = "JAWS"
    Purpose     = "Terraform state storage"
  }
}

# Enable versioning to protect against accidental deletions
resource "aws_s3_bucket_versioning" "terraform_state" {
  bucket = aws_s3_bucket.terraform_state.id

  versioning_configuration {
    status = "Enabled"
  }
}

# Enable encryption at rest
resource "aws_s3_bucket_server_side_encryption_configuration" "terraform_state" {
  bucket = aws_s3_bucket.terraform_state.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

# Block public access
resource "aws_s3_bucket_public_access_block" "terraform_state" {
  bucket = aws_s3_bucket.terraform_state.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

# DynamoDB table for state locking
resource "aws_dynamodb_table" "terraform_locks" {
  name         = "jaws-terraform-locks"
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "LockID"

  attribute {
    name = "LockID"
    type = "S"
  }

  tags = {
    Name        = "JAWS Terraform State Locks"
    Application = "JAWS"
    Purpose     = "Terraform state locking"
  }
}

# Get current AWS account ID for bucket naming
data "aws_caller_identity" "current" {}
