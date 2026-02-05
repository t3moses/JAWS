output "instance_ip" {
  description = "Public IP address for the Lightsail instance."
  value       = aws_lightsail_static_ip.app.ip_address
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
