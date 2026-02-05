variable "aws_region" {
  description = "AWS region for Lightsail (hardcoded to Canada)."
  type        = string
  default     = "ca-central-1"
}

variable "availability_zone" {
  description = "Availability zone in Canada."
  type        = string
  default     = "ca-central-1a"
}

variable "instance_name" {
  description = "Lightsail instance name."
  type        = string
  default     = "jaws-production"
}

variable "blueprint_id" {
  description = "Lightsail blueprint ID (Ubuntu 22.04)."
  type        = string
  default     = "ubuntu_22_04"
}

variable "bundle_id" {
  description = "Lightsail bundle size."
  type        = string
  default     = "small_1_0"
}

variable "domain_name" {
  description = "Domain name for DNS (Route 53 section is commented out)."
  type        = string
  default     = "nsc-sdc.ca"
}

# If the domain owner provides an existing Lightsail key pair name,
# set it here and remove the generated key pair in main.tf.
variable "existing_key_pair_name" {
  description = "Existing Lightsail key pair name (leave blank to auto-generate)."
  type        = string
  default     = ""
}
