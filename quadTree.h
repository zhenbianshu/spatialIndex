#ifndef _CP_QUAD_TREE_H_

#define _CP_QUAD_TREE_H_

#define MAX_ELE_NUM 100

#define QUADRANT_RU 1
#define QUADRANT_LU 2
#define QUADRANT_LB 3
#define QUADRANT_RB 4

struct Region {
    double up;
    double bottom;
    double left;
    double right;
};

struct ElePoint {
    double lng;
    double lat;
    char desc[16];
};

struct QuadTreeNode {
    int depth;
    int is_leaf;
    struct Region region;
    struct QuadTreeNode *LU;
    struct QuadTreeNode *LB;
    struct QuadTreeNode *RU;
    struct QuadTreeNode *RB;
    int ele_num;
    struct ElePoint *ele_list[MAX_ELE_NUM];
};

void initNode(struct QuadTreeNode *node, int depth, struct Region region);

void insertEle(struct QuadTreeNode *node, struct ElePoint ele);

void deleteEle(struct QuadTreeNode *node, struct ElePoint ele);

void splitNode(struct QuadTreeNode *node);

void combineNode(struct QuadTreeNode *node);

void queryEle(struct QuadTreeNode tree, struct ElePoint ele);

void initRegion(struct Region *region, double up, double bottom, double left, double right);

struct QuadTreeNode *createChildNode(struct QuadTreeNode *node, double bottom, double up, double left, double right);
#endif