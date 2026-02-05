terraform {
  required_version = ">= 1.5.0"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    tls = {
      source  = "hashicorp/tls"
      version = "~> 4.0"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.5"
    }
  }
}

provider "aws" {
  region = var.aws_region
}

locals {
  create_key_pair = var.existing_key_pair_name == ""
}

resource "random_id" "key_suffix" {
  count       = local.create_key_pair ? 1 : 0
  byte_length = 4
}

resource "tls_private_key" "generated" {
  count     = local.create_key_pair ? 1 : 0
  algorithm = "RSA"
  rsa_bits  = 4096
}

resource "aws_lightsail_key_pair" "generated" {
  count      = local.create_key_pair ? 1 : 0
  name       = "jaws-lightsail-key-${random_id.key_suffix[0].hex}"
  public_key = tls_private_key.generated[0].public_key_openssh
}

locals {
  key_pair_name = local.create_key_pair ? aws_lightsail_key_pair.generated[0].name : var.existing_key_pair_name
}

resource "aws_lightsail_instance" "app" {
  name              = var.instance_name
  availability_zone = var.availability_zone
  blueprint_id      = var.blueprint_id
  bundle_id         = var.bundle_id
  key_pair_name     = local.key_pair_name
  tags = {
    Application = "JAWS"
    Environment = "production"
  }
}

resource "aws_lightsail_static_ip" "app" {
  name = "${var.instance_name}-static-ip"
}

resource "aws_lightsail_static_ip_attachment" "app" {
  static_ip_name = aws_lightsail_static_ip.app.name
  instance_name  = aws_lightsail_instance.app.name
}

resource "aws_lightsail_instance_public_ports" "app" {
  instance_name = aws_lightsail_instance.app.name

  port_info {
    from_port = 80
    to_port   = 80
    protocol  = "tcp"
  }

  port_info {
    from_port = 443
    to_port   = 443
    protocol  = "tcp"
  }

  # SSH access is required for deployment. Remove this only if you use a different access method.
  port_info {
    from_port = 22
    to_port   = 22
    protocol  = "tcp"
  }
}

# Route 53 DNS (commented out because the domain is owned by someone else)
# Uncomment and update the hosted zone lookup when the domain owner is ready.
#
# data "aws_route53_zone" "primary" {
#   name         = var.domain_name
#   private_zone = false
# }
#
# resource "aws_route53_record" "root_a" {
#   zone_id = data.aws_route53_zone.primary.zone_id
#   name    = var.domain_name
#   type    = "A"
#   ttl     = 300
#   records = [aws_lightsail_static_ip.app.ip_address]
# }
