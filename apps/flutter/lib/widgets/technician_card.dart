import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../models/technician.dart';

class TechnicianCard extends StatelessWidget {
  final TechnicianModel technician;
  final VoidCallback? onTap;

  const TechnicianCard({super.key, required this.technician, this.onTap});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 140,
        margin: const EdgeInsets.only(right: 12),
        decoration: BoxDecoration(
          color: theme.colorScheme.surface,
          borderRadius: BorderRadius.circular(12),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 6,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 12),
            ClipOval(
              child: technician.avatar != null
                  ? CachedNetworkImage(
                      imageUrl: technician.avatar!,
                      width: 60,
                      height: 60,
                      fit: BoxFit.cover,
                      placeholder: (_, __) => Container(
                        width: 60, height: 60, color: Colors.grey[200],
                        child: const Icon(Icons.person, color: Colors.grey),
                      ),
                      errorWidget: (_, __, ___) => Container(
                        width: 60, height: 60, color: Colors.grey[200],
                        child: const Icon(Icons.person, color: Colors.grey),
                      ),
                    )
                  : Container(
                      width: 60,
                      height: 60,
                      decoration: BoxDecoration(
                        color: Colors.grey[200],
                        shape: BoxShape.circle,
                      ),
                      child: Icon(Icons.person, color: Colors.grey[600], size: 30),
                    ),
            ),
            const SizedBox(height: 8),
            Text(
              technician.name,
              style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            if (technician.rating != null)
              Padding(
                padding: const EdgeInsets.only(top: 2),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.star, size: 14, color: Colors.amber[600]),
                    const SizedBox(width: 2),
                    Text(
                      technician.rating!.toStringAsFixed(1),
                      style: theme.textTheme.bodySmall?.copyWith(color: Colors.amber[700]),
                    ),
                  ],
                ),
              ),
            if (technician.intro != null)
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                child: Text(
                  technician.intro!,
                  style: theme.textTheme.bodySmall?.copyWith(color: Colors.grey),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            const SizedBox(height: 12),
          ],
        ),
      ),
    );
  }
}
