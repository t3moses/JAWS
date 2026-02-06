output "instance_ip" {
  description = "Public IP address for the Lightsail instance."
  value       = aws_lightsail_instance.app.public_ip_address
}

output "key_pair_name" {
  description = "Lightsail key pair name in use."
  value       = local.key_pair_name
}

output "ssh_private_key" {
  description = "Generated SSH private key (empty if using an existing key pair)."
  value       = local.create_key_pair ? tls_private_key.generated[0].private_key_pem : ""
  sensitive   = true
}

output "terraform_state_bucket" {
  description = "S3 bucket name for Terraform state storage"
  value       = aws_s3_bucket.terraform_state.id
}

output "terraform_locks_table" {
  description = "DynamoDB table name for Terraform state locking"
  value       = aws_dynamodb_table.terraform_locks.id
}
